<?php namespace Wehnhew\Payments\Adapters;

use Illuminate\Support\Facades\Config as Config;
use Illuminate\Support\Facades\Log as Log;

class Chinabank extends AdapterAbstract{

	/**
	 * 构建支付适配器
	 *
	 * @access public
	 * @param  array $config (default: array())
	 * @return void
	 */
	public function __construct($config = array())
	{
		if (!empty($config)) $this->setConfig($config);
		$charset = Config::get('app.encoding','UTF-8');
		$this->_config['url'] = 'https://pay3.chinabank.com.cn/PayGate?encoding='.$charset;
		$this->_config['method'] = 'POST';
	}
	/**
	 * 初始化表单参数
	 *
	 * @access public
	 * @param  array $params (default: array())
	 * @return void
	 */
	public function _getParameters() {
        $passParameters = array(
			'v_mid'       	=> $this->_config['account'],//商户编号 
			'v_oid'       	=> $this->_orderid,//订单编号
			'v_amount'   	=> $this->_product['price'],//商品金额
			'v_moneytype' 	=> $this->_product['currency'],//商品币种
			'v_url'       	=> $this->_config['reurl'],//返回地址
			'v_rcvname'   	=> $this->_customer['name'],//收货人姓名
			'v_rcvaddr'   	=> $this->_customer['address'],//收货人地址
			'v_rcvtel'    	=> $this->_customer['tel'],//收货人电话
			'v_rcvmobile' 	=> $this->_customer['mobile'],//收货人手机号
			'v_rcvemail'  	=> $this->_customer['email'],//收货人邮箱
			'v_rcvpost'   	=> $this->_customer['post'],//收货人邮编
			'v_ordername' 	=> $this->_orderer['name'],//购买人姓名
			'v_orderaddr' 	=> $this->_orderer['address'],//购买人地址
			'v_ordertel'  	=> $this->_orderer['tel'],//购买人电话
			'v_ordermobile'	=> $this->_orderer['mobile'],//购买人手机号
			'v_orderemail'	=> $this->_orderer['email'],//购买人邮箱
			'v_orderpost' 	=> $this->_orderer['post'],//购买人邮编
			'remark1'    	=> $this->_orderer['remark1'],//商品备注1
			'remark2'     	=> $this->_orderer['remark2'],//商品备注2
        );
		//数字签名	
		$signature = $passParameters['v_amount'].$passParameters['v_moneytype'].$passParameters['v_oid'];
		$signature .= $passParameters['v_mid'].$passParameters['v_url'].$this->_config['key']; 
		$passParameters['v_md5info'] = strtoupper(md5($signature));
		return $passParameters;
	}
	
	/**
	 * 客户端接收数据
	 * 状态码说明  （1 交易完成 0 交易失败）
	 */
    public function receive($result) {
    	$result = $this->filterParameter($result);
		$return['status'] = 0;
    	if ($result)
		{
			$return = array();
			$v_oid     =trim($result['v_oid']);
			$v_pmode   =trim($result['v_pmode']);  
			$v_pstatus =trim($result['v_pstatus']);
			$v_pstring =trim($result['v_pstring']);
			$v_amount  =trim($result['v_amount']);
			$v_moneytype  =trim($result['v_moneytype']);
			$remark1   = isset($result['remark1' ])?trim($result['remark1' ]):'';
			$remark2   = isset($result['remark2' ])?trim($result['remark2' ]):'';
			$v_md5str  =trim($result['v_md5str' ]); 
			$md5string=strtoupper(md5($v_oid.$v_pstatus.$v_amount.$v_moneytype.$this->_config['key']));
			if ($v_md5str==$md5string)
			{
				$return['oid'] = $v_oid;
				$return['data'] = $v_pmode;
                $return['note'] = '支付方式：'.$v_pmode.'支付结果信息：'.$v_pstring;
				if($v_pstatus == '20')
				{
					$return['status'] = 1;
				}
				else
				{
					Log::error(date('m-d H:i:s').'| chinabank order='.$v_oid.' status='.$v_pstatus);
				}
			}
			else
			{
				Log::error(date('m-d H:i:s').'| chinabank  order='.$v_oid.' 非法sign');
			}
		}
		else
		{
			Log::error(date('m-d H:i:s').'| chinabank no return!'."\r\n");
		}
		return $return;	
    }
    	
    /**
     * 相应服务器应答状态
     * @param $result
     */
    public function response($result)
	{
    	if (TRUE == $result) echo 'ok';
		else echo 'error';
    }
    
    /**
     * 返回字符过滤
     * @param $parameter
     */
	private function filterParameter($parameter)
	{
		$para = array();
		foreach ($parameter as $key => $value)
		{
			if ('sign' == $key || 'sign_type' == $key || '' == $value || 'code' == $key )
			{
				continue;
			}
			else
			{
				$charset = Config::get('app.encoding','UTF-8');
				if($charset == 'UTF-8')
				{
					$para[$key] = iconv('GBK',$charset,$value);
				}
			}
		}
		return $para;
	}
}
?>