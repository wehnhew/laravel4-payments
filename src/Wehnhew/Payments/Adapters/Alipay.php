<?php namespace Wehnhew\Payments\Adapters;

use Illuminate\Support\Facades\Config as Config;
use Illuminate\Support\Facades\Log as Log;

class Alipay extends AdapterAbstract{

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
	    if ($this->_config['type'] == 1 ) $this->_config['service'] = 'trade_create_by_buyer';
		elseif( $this->_config['type']== 2 ) $this->_config['service'] = 'create_direct_pay_by_user';
        else $this->_config['service'] = 'create_partner_trade_by_buyer';
		$this->_config['charset'] = Config::get('app.encoding','UTF-8');
		$this->_config['url'] = 'https://www.alipay.com/cooperate/gateway.do?_input_charset='.$this->_config['charset'];
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
			'out_trade_no' 	=> $this->_orderid,//订单编号 
			'quantity' 		=> 1,
			'seller_email' 	=> $this->_config['account'],//商户编号 
			'partner'       => $this->_config['partner'],//订单编号
			'service'   	=> $this->_config['service'],//订单总金额
			'payment_type' 	=> 1,//币种
			'subject'		=> $this->_product['name'],//商品名称
			'url'			=> $this->_product['url'],//商品链接
			'body'   		=> $this->_product['info'],//商品信息
			'_input_charset'=> $this->_config['charset'],//编码
			'notify_url'	=> $this->_config['reurl'],//返回地址
			'return_url'	=> $this->_config['reurl'],//返回地址
			'buyer_email'   => $this->_orderer['email'],//购买人邮箱
			'remark1'    	=> $this->_orderer['remark1'],//备注1
			'remark2'     	=> $this->_orderer['remark2'],//备注2
        );
		// 物流信息
		if($this->_config['service'] == 'create_partner_trade_by_buyer' || $this->_config['service'] == 'trade_create_by_buyer') {
			$passParameters['logistics_type'] = 'EXPRESS';
			$passParameters['logistics_fee'] = '0.00';
			$passParameters['logistics_payment'] = 'SELLER_PAY';
		}
		//数字签名	
		$passParameters['sign'] = $this->_build_mysign($passParameters,$this->_config['key'],'MD5');
		
		return $passParameters;
	}
	
	/**
	 * 客户端接收数据
	 * 状态码说明  （1 交易完成 0 交易失败）
	 */
    public function receive($result) {
		$receiveSign = isset($result['sign'])?$result['sign']:'';
    	$result = $this->_filterParameter($result);
		$return = array();
		$return['status'] = 0;
    	if ($result)
		{
			$verifyResult = $this->_makeRequest('http://notify.alipay.com/trade/notify_query.do?partner=' . $this->_config['partner'] . '&notify_id=' . $result['notify_id']);
			if (preg_match('/true$/i', $verifyResult))
			{
				$sign = '';
				$sign = $this->_build_mysign($result,$this->_config['key'],'MD5');				
				if ($sign != $receiveSign)
				{
					Log::error(date('m-d H:i:s').'| alipay signature is bad');
				}
				else
				{
					$return['oid'] = $result['out_trade_no'];
					$return['data'] = '';
					$return['money'] = $result['price'];
					$return['note'] = '邮箱地址:'.$result['buyer_email'];
					switch ($result['trade_status'])
					{
						case 'WAIT_BUYER_PAY': $result['status'] = 3; break;
						case 'WAIT_SELLER_SEND_GOODS': $result['status'] = 3; break;
						case 'WAIT_BUYER_CONFIRM_GOODS': $result['status'] = 3; break;
						case 'TRADE_CLOSED': $result['status'] = 5; break;
						case 'TRADE_FINISHED': $result['status'] = 1; break;
						case 'TRADE_SUCCESS': $result['status'] = 1; break;
						default:
							 $result['status'] = 0;
					}
				}
			}
			else
			{
                Log::error(date('m-d H:i:s').'| alipay GET: illegality notice : flase |');
			}
		}
		else
		{
            Log::info(date('m-d H:i:s').'| GET: no return |');
		}
		
		return $return;	
    }
    	
    /**
     * 相应服务器应答状态
     * @param $result
     */
    public function response($result)
	{
    	if (FALSE == $result) echo 'fail';
		else echo 'success';
    }
	/**
	 * 生成签名结果
	 * @param $array 要加密的数组
	 * @param return 签名结果字符串
	*/
	private function _build_mysign($array,$security_code,$sign_type = "MD5") 
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr  = "";
		while (list ($key, $val) = each ($array))
		{
			$prestr.=$key."=".$val."&";
		}
		$prestr= substr($prestr,0,count($prestr)-2); //去掉最后一个&字符
		
		$prestr = $prestr.$security_code;		  //把拼接后的字符串再与安全校验码直接连接起来
		
		//把最终的字符串加密，获得签名结果
		if($sign_type == 'MD5')
		{
			$mysgin = md5($prestr);
		}
		elseif($sign_type =='DSA')
		{
			//DSA 签名方法待后续开发
			die('dsa');
		}
		else
		{
			die('alipay_error');
		}	    
		return $mysgin;
	}
    /**
     * 返回字符过滤
     * @param $parameter
     */
	private function _filterParameter($parameter)
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
				$para[$key] = $value;
			}
		}
		return $para;
	}
}
?>