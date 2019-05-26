<?php
/**
 * Created by PhpStorm.
 * User:  蓝伟清
 * Email：lwqbrell@qq.com
 * Date:  2019/5/26
 */
define("CURL_TIMEOUT",   10);
define("URL",            "http://api.fanyi.baidu.com/api/trans/vip/translate");
define("APP_ID",         "20190525000301598");   //替换为您的APPID
define("SEC_KEY",        "dRFWHSDJ36C_A05eopnf");//替换为您的密钥

interface TranslatorInterface
{
    public function translateOne($word);

    public function translateMany($words);
}

/**
 * Class BaiduTranslator [西洋汇笔试] 百度通用翻译SDK
 */
class BaiduTranslator implements TranslatorInterface
{
    private  $_from;
    private $_to;
    public  $args;
    public $words;
    public function __construct($from,$to)
    {
        try{
            if (empty($from)){
                $this->_from='auto';
            }else{
                if ($this->isTranslateSupport($from)){
                    $this->_from=$from;
                }else{
                    throw new Exception('语种格式不支持,请参考：http://fanyi-api.baidu.com/api/trans/product/apidoc#joinFile','10001');
                }
            }
            if (empty($to)){
                $this->_to='en';
            }else{
                if ($this->isTranslateSupport($to)){
                    $this->_to=$to;
                }else{
                    throw new Exception('翻译格式不支持,请参考：http://fanyi-api.baidu.com/api/trans/product/apidoc#joinFile','10002');
                }
            }
        }catch (Exception $e){
            var_dump($e->getMessage(),$e->getCode());
            die;
        }
    }

    /**
     * @param  string $word 单句翻译原文
     * @return string mixed 单句翻译译文
     */
    public function translateOne($word){
        $this->getArgs($word);
        return $this->getWords();
    }

    /**
     * @param  array $words 多句翻译原文
     * @return array $data  多句翻译译文
     */
    public function translateMany($words){
        $data=[];
        if (!is_array($words)){
            throw new Exception('请以数组的形式发起请求','10002');
        }
        foreach ($words as $value){
            $this->getArgs($value);
            array_push($data,$this->getWords());
        }
        return $data;
    }

    /**
     * @param string $word 原文
     */
    public function getArgs($word){
        $this->words=$word;
        $this->args = array(
            'q' => $word,
            'appid' => APP_ID,
            'salt' => rand(10000,99999),
            'from' => $this->_from,
            'to' => $this->_to,
        );
    }

    /**
     * @return string mixed 译文
     */
    public function getWords(){
        $this->args['sign'] = $this->buildSign($this->words, APP_ID, $this->args['salt'], SEC_KEY);
        $ret = $this->call(URL, $this->args);
        $ret = json_decode($ret, true);
        return $ret['trans_result'][0]['dst'];
    }

    /**
     * @param  string $lang 语种
     * @return bool
     */
    public function isTranslateSupport($lang){
        $languages=['auto','zh','en','yue','wyw','jp','kor','fra','spa','th','ara','ru',
            'pt','de','it','el','nl','pl','bul','est','dan','fin','cs','rom','slo',
            'swe','hu','cht','vie'];
        if (in_array($lang,$languages)){
            return true;
        }else{
            return false;
        }
    }

    //加密
    /**
     * @param string $query
     * @param string $appID
     * @param string $salt
     * @param string $secKey
     * @return string
     */
    function buildSign($query, $appID, $salt, $secKey)
    {
        $str = $appID . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }

    //发起网络请求
    /**
     * @param  string $url
     * @param  null $args
     * @param  string $method
     * @param  int $testflag
     * @param  int $timeout
     * @param  array $headers
     * @return bool|mixed
     */
    function call($url, $args=null, $method="post", $testflag = 0, $timeout = CURL_TIMEOUT, $headers=array())
    {
        $ret = false;
        $i = 0;
        while($ret === false)
        {
            if($i > 1)
                break;
            if($i > 0)
            {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;

    }

    /**
     * @param  string $url
     * @param  null $args
     * @param  string $method
     * @param  bool $withCookie
     * @param  int $timeout
     * @param  array $headers
     * @return mixed
     */
    function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = CURL_TIMEOUT, $headers=array())
    {
        $ch = curl_init();
        if($method == "post")
        {
            $data = $this->convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        else
        {
            $data = $this->convert($args);
            if($data)
            {
                if(stripos($url, "?") > 0)
                {
                    $url .= "&$data";
                }
                else
                {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if($withCookie)
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    /**
     * @param  array $args
     * @return string
     */
    function convert(&$args)
    {
        $data = '';
        if (is_array($args))
        {
            foreach ($args as $key=>$val)
            {
                if (is_array($val))
                {
                    foreach ($val as $k=>$v)
                    {
                        $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                    }
                }
                else
                {
                    $data .="$key=".rawurlencode($val)."&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }

}

// 实例化百度通用翻译对象,第一个参数为原文语种(可设为自动获取auto)，第二个参数为译文语种
$t=new BaiduTranslator('auto','en');
// 单句翻译
$word=$t->translateOne('好好学习');
var_dump($word);
// 多句翻译
$words=$t->translateMany(array('不忘初心','与时俱进'));
var_dump($words);

/**
 * 百度通用翻译SDK使用说明
 * 1.将define("APP_ID","20190525000301598");    //替换为您的APPID
 * 2.将define("SEC_KEY","dRFWHSDJ36C_A05eopnf");//替换为您的密钥
 * 3.实例化对象时需要传原文语种和译文语种参数
 * 4.调用translateOne($word)翻译单句
 * 5.调用translateMany($word)翻译多句
 */