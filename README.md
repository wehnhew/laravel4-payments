## Payments for Laravel 4

Laravel 4 的支付网关.

[![Latest Stable Version](https://poser.pugx.org/wehnhew/laravel4-payments/v/stable.png)](https://packagist.org/packages/wehnhew/laravel4-payments)[![Total Downloads](https://poser.pugx.org/wehnhew/laravel4-payments/downloads.png)](https://packagist.org/packages/wehnhew/laravel4-payments)

### 安装

- [laravel4-payments on Packagist](https://packagist.org/packages/laravel4-payments)
- [laravel4-payments on GitHub](https://github.com/wehnhew/laravel4-payments)

只要在你的 `composer.json` 文件require中加入下面内容，就能获得最新版.

~~~
"wehnhew/payments": "dev-master"
~~~

然后需要运行 "composer update" 来更新你的项目

安装完后，在 `app/config/app.php` 文件中找到 `providers` 键，

~~~
'providers' => array(

    'Wehnhew\Payments\PaymentServiceProvider'

)
~~~

找到 `aliases` 键，

~~~
'aliases' => array(

    'Payment' => 'Wehnhew\Payments\Facades\Payment'

)
~~~

## 以网银接口为例
网银配置

~~~php
$config = array(
	'account'=>'1001',//商户账号
	'key'=>'test', //商户密钥
	'reurl'=>'http://www.domain/payments/respond' //支付返回地址
);
~~~

生成支付表单

~~~php
$adapter = Payment::create('chinabank',$config);
$payFrom = $pay->setOrderid('0001') //订单ID
			->setProduct(['price'=>100.01]) //商品价钱
			->setCustomer(['name'=>'文文','mobile'=>1380000000]) //购买人名称，手机
			->render(); //生成表单
var_dump($payFrom);
~~~

支付返回处理

~~~php
$data = Input::all();
$result = Payment::create('chinabank',$config)
			->receive($data);
var_dump($result);
if($return['status'] > 0)
{
	//success
}
else
{
	//fail
}
~~~

## 联系我
有问题，请发送到 chenhanwen@163.com
