<?php
define("CURL_TIMEOUT",   10);
define("URL",            "http://api.fanyi.baidu.com/api/trans/vip/translate");
define("APP_ID",         "20190525000301598"); //替换为您的APPID
define("SEC_KEY",        "dRFWHSDJ36C_A05eopnf");//替换为您的密钥

interface TranslatorInterface
{
    public function translateOne($word);

    public function translateMany($words);
}


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
                    throw new Exception('语种格式不支持','10001');
                }
            }
            if (empty($to)){
                $this->_to='en';
            }else{
                if ($this->isTranslateSupport($to)){
                    $this->_to=$to;
                }else{
                    throw new Exception('翻译格式不支持','10002');
                }
            }
        }catch (Exception $e){
           var_dump($e->getMessage(),$e->getCode());
           die;
        }
    }

    public function translateOne($word){
        $this->getArgs($word);
        return $this->getWords();
    }

    public function translateMany($words){
        $data=[];
        if (!is_array($words)){
            throw new \Exception('请以数组的形式发起请求','10001');
        }
        foreach ($words as $value){
            $this->getArgs($value);
            array_push($data,$this->getWords());
        }
        return $data;
    }

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
    public function getWords(){
        $this->args['sign'] = $this->buildSign($this->words, APP_ID, $this->args['salt'], SEC_KEY);
        $ret = $this->call(URL, $this->args);
        $ret = json_decode($ret, true);
        return $ret['trans_result'][0]['dst'];
    }

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
    function buildSign($query, $appID, $salt, $secKey)
    {
        $str = $appID . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }

    //发起网络请求
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

$t=new BaiduTranslator('','asasdd');
$res=$t->translateMany(['去睡觉吧','好像是你的']);
var_dump($res);