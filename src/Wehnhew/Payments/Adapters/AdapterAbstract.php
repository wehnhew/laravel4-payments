<?php namespace Wehnhew\Payments\Adapters;

abstract class AdapterAbstract{
	/**
	 * 支付配置
	 *
	 * @var  array
	 */
	protected $_config = array(
		'account'	=> '',//商户账号
		'key'		=> '',//商户密钥
		'partner'		=> 0,//合作身份者ID
		'type'		=> 0,//支付网关类型
		'url'		=> '',//支付网关地址
		'reurl'		=> '',//支付返回地址
		'method'	=> 'POST',//支付网关方法
		
	);
	/**
	 * 商品信息
	 *
	 * @var  array
	 */
	protected $_product = array(
		'name'		=> '',//名称
		'price'		=> 0,//金额
		'info'		=> '',//信息
		'url'		=> '',//链接
		'currency'	=> 'CNY',//币种
	);
	/**
	 * 订单编号
	 *
	 * @var  array
	 */
	protected $_orderid;
	/**
	 * 购买人信息
	 *
	 * @var  array
	 */
	protected $_orderer = array(
		'name'		=> '',//姓名
		'address'	=> '',//地址
		'tel'		=> '',//电话
		'mobile'	=> '',//手机
		'email'		=> '',//邮箱
		'post'		=> '',//邮编
		'remark1'	=> '',//备注1
		'remark2'	=> ''//备注2
	);
	/**
	 * 收货人信息
	 *
	 * @var  array
	 */
	protected $_customer = array(
		'name'		=> '',//姓名
		'address'	=> '',//地址
		'tel'		=> '',//电话
		'mobile'	=> '',//手机
		'email'		=> '',//邮箱
		'post'		=> ''//邮编
	);

	/**
	 * 设置配置参数.
	 *
	 * @param  array
	 * @return object
	 */
	public function setConfig($config)
	{
		$this->_config = array_merge($this->_config,$config);
		return $this;
	}
	/**
	 * 设置商品信息.
	 *
	 * @param  array
	 * @return object
	 */
	public function setProduct($product)
	{
		$this->_product = array_merge($this->_product,$product);
		return $this;
	}
	/**
	 * 设置客户信息.
	 *
	 * @param  array
	 * @return object
	 */
	public function setCustomer($customer)
	{
		$this->_customer= array_merge($this->_customer,$customer);;
		return $this;
	}
	/**
	 * 设置收货人信息.
	 *
	 * @param  array
	 * @return object
	 */
	public function setOrderer($orderer)
	{
		$this->_orderer = array_merge($this->_orderer,$orderer);
		return $this;
	}
	/**
	 * 设置订单编号.
	 *
	 * @param  array
	 * @return object
	 */
	public function setOrderid($orderid)
	{
		$this->_orderid = $orderid;
		return $this;
	}
	/**
	 * 生成支付表单.
	 *
	 * @param  array
	 * @param  string
	 * @return string
	 */
	public function render()
	{
		$parameters = $this->_getParameters();
		foreach ($parameters as $attr_key => $attr_val)
		{
			$hiddens[] = '<input type="hidden" name="'.$attr_key.'" value="'.$attr_val.'" />' . "\n";
		}
		$form = '
			<form method="'.$this->_config['method'].'" action="'.$this->_config['url'].'" id="'.$this->_orderid.'gateway" target="_blank">
				'.implode('', $hiddens).'
			</form>
		';
		return $form;
	}
	/**
	 * 支付验证确认.
	 *
	 * @param  array
	 * @param  string
	 * @return string
	 */
	protected function _makeRequest($url,$time_out = "60")
	{
		$urlarr     = parse_url($url);
		$errno      = "";
		$errstr     = "";
		$transports = "";
		if($urlarr["scheme"] == "https")
		{
			$transports = "ssl://";
			$urlarr["port"] = "443";
		}
		else
		{
			$transports = "tcp://";
			$urlarr["port"] = "80";
		}
		$fp = @fsockopen($transports . $urlarr['host'],$urlarr['port'],$errno,$errstr,$time_out);
		if(!$fp)
		{
			die("ERROR: $errno - $errstr<br />\n");
		}
		else
		{
			fputs($fp, "POST ".$urlarr["path"]." HTTP/1.1\r\n");
			fputs($fp, "Host: ".$urlarr["host"]."\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: ".strlen($urlarr["query"])."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $urlarr["query"] . "\r\n\r\n");
			while(!feof($fp))
			{
				$info[] = @fgets($fp, 1024);
			}
			fclose($fp);
			$info = implode(",",$info);
			return $info;
		}
	}
	/**
	 * 支付返回通知
	 */	
	abstract public function receive($result);
	/**
	 * 支付返回响应
	 */	
	abstract public function response($result);
	/**
	 * 支付表单参数
	 */	
	abstract public function _getParameters();
}