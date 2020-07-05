<?php


namespace TpCheck;


use TpCheck\ParamException;
use think\facade\Request;

class Validate extends \think\Validate
{
    const ACTION_ONLY = "only";
    const ACTION_MANY = "many";
    const ACTION_ARR_MANY = "arr_many";

    protected static $models = [];

    protected $params = [];
    protected $isArr = false;
    protected $action = "";
    protected $index = 0;
    protected $nodeAction = "";


    /**
     * @param string $name
     * @param null $value
     * @return mixed|null
     */
    public static function M($name, $value = null) {
        if(is_null($value)) {
            return isset(static::$models[$name]) ? static::$models[$name] : null;
        } else {
            static::$models[$name] = $value;
        }
        return null;
    }

    /**
     * 设置方法
     * @param string $action
     * @return $this
     */
    protected function setAction($action = "only") {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置子节点方式
     * @param string $action
     * @return $this
     */
    protected function setNodeAction($action = "many") {
        $this->nodeAction = $action;
        return $this;
    }

    /**
     * 设置参数
     * @param null $params
     * @param bool $isArr
     * @return $this
     */
    protected function setParams($params = null, $isArr = false) {
        $this->isArr = $isArr;
        if(!is_array($params)) {
            $params = Request::param();
        }
        if(!$this->isArr) {
            $params = [$params];
        }
        $this->params = $params;
        return $this;
    }

//    /**
//     * 验证参数
//     * @throws ParamException
//     */
    protected function checkCurrent() {
        foreach ($this->params as $param) {
            if(!$this->check($param)) {
                throw new ParamException($this->error);
            }
        }
        return $this;
    }

    /**
     * @param $var
     * @param $rule
     * @return array
     */
    protected function formatVar($var, $rule) {
        if(is_array($rule)) {
            $arr = array_keys($rule);

            foreach ($arr as $k => $v) {
                if(is_int($v)) {
                    $arr[$k] = $rule[$v];
                }
            }
            $rule = $arr;
            $bool = false;
            foreach (["array", "f_array", "validate"] as $v) {
                if(in_array($v, $rule)) {
                    $bool = true;
                }
            }
        } else {
            $bool = strpos($rule, "array") !== false || strpos($rule, "validate") !== false;
        }
        if($bool) {
            return is_array($var) ? $var : [];
        }
        return $var;
    }

    /**
     * 获取所有
     * @return array
     */
    protected function getMany() {
        $arr = [];
        foreach ($this->params as $k => $param) {
            $this->index = $k;
            $iArr = [];
            foreach ($this->rule as $r => $rv) {
                if(strpos($r, "|") !== false) {
                    $r = explode("|", $r)[0];
                }
                if($this->currentScene) {
                    if(in_array($r, $this->scene[$this->currentScene])) {
                        $iArr[$r] = $this->formatVar($param[$r] ?? "", $rv);
                    }
                } else {
                    $iArr[$r] = $this->formatVar($param[$r] ?? "", $rv);
                }
            }
            if(!$this->isArr) {
                return $iArr;
            }
            $arr[] = $iArr;
        }
        return $arr;
    }

    /**
     * 获取所有 按数组返回
     * @return array
     */
    protected function getManyToArray() {
        $arr = [];
        foreach ($this->params as $k => $param) {
            $this->index = $k;
            $iArr = [];
            foreach ($this->rule as $r => $rv) {
                if(strpos($r, "|") !== false) {
                    $r = explode("|", $r)[0];
                }
                if($this->currentScene) {
                    if(in_array($r, $this->scene[$this->currentScene])) {
                        $iArr[] = $this->formatVar($param[$r] ?? "", $rv);
                    }
                } else {
                    $iArr[] = $this->formatVar($param[$r] ?? "", $rv);
                }
            }
            if(!$this->isArr) {
                return $iArr;
            }
            $arr[] = $iArr;
        }
        return $arr;
    }

    /**
     * 获取唯一
     * @return array
     */
    protected function getOnly() {
        $arr = [];
        foreach ($this->params as $k => $param) {
            $this->index = $k;
            $iArr = [];
            foreach ($this->rule as $r => $rv) {
                if(strpos($r, "|") !== false) {
                    $r = explode("|", $r)[0];
                }
                if($this->currentScene) {
                    if(in_array($r, $this->scene[$this->currentScene])) {
                        if(strpos($rv, "require") !== false) {
                            $iArr[$r] = $this->formatVar($param[$r] ?? "", $rv);
                        } else {
                            if(isset($param[$r])) {
                                $iArr[$r] = $this->formatVar($param[$r] ?? "", $rv);
                            }
                        }
                    }
                } else {
                    if(strpos($rv, "require") !== false) {
                        $iArr[$r] = $this->formatVar($param[$r] ?? "", $rv);
                    } else {

                        if(isset($param[$r])) {
                            $iArr[$r] = $this->formatVar($param[$r] ?? "", $rv);
                        }
                    }
                }
            }
            if(!$this->isArr) {
                return $iArr;
            }
            $arr[] = $iArr;
        }
        return $arr;
    }

    /**
     * @return mixed
     */
    protected function get() {
        if($this->action == self::ACTION_MANY) {
            return $this->getMany();
        } else if($this->action == self::ACTION_ONLY) {
            return $this->getOnly();
        } else if($this->action == self::ACTION_ARR_MANY) {
            return $this->getManyToArray();
        }
        return "";
    }

    // 验证验证类 用法 validate:score,type,class
    protected function validate($value, $rule, $data=[], $field="") {
        if(strpos($rule, ",") !== false) {
            $rule = explode(",", $rule);
            $len = count($rule);
            if($len > 2) { // score, type, class
                $rScene = $rule[0];
                $rType  = $rule[1];
                $rClass = $rule[2];
            } else if($len > 1) { // type, class
                $rScene = "";
                $rType  = $rule[0];
                $rClass = $rule[1];
            } else { // class
                $rScene = "";
                $rType  = "map";
                $rClass = $rule[0];
            }
        } else {
            $rScene = "";
            $rType  = "map";
            $rClass = $rule;
        }

        if(!$this->nodeAction) {
            if(in_array($this->action, [self::ACTION_MANY, self::ACTION_ARR_MANY])) {
                $rAction = self::ACTION_MANY;
            } else {
                $rAction = self::ACTION_ONLY;
            }
        } else {
            $rAction = $this->nodeAction;
        }

        $value = (new $rClass)
            ->setParams(is_array($value) ? $value : [], $rType != "map")
            ->scene($rScene)
            ->checkCurrent()
            ->setAction($rAction)
            ->get();

        $this->params[$this->index][$field] = $value == "" ? [] : $value;
        return true;
    }

    protected function empty($value, $rule, $data = [], $field = "") {
        return true;
    }

    protected function array($value, $rule, $data = [], $field = "") {
        if(!is_array($value)) {
            return "{$field}必须是数组";
        }
        if(!in_array($rule, ["int", "float", "number"])) {
            return true;
        }
        foreach ($value as $v) {
            if($rule == "int") {
                if(!is_integer($v)) {
                    return "{$field}必须是整数数组";
                }
            } else if($rule == "float") {
                if(!is_float($v)) {
                    return "{$field}必须是浮点数组";
                }
            } else if($rule == "number") {
                if(!is_numeric($v)) {
                    return "{$field}必须是数字数组";
                }
            }
        }
        return true;
    }

//    protected function require($value, $rule, $data = [], $field = "") {
//        if(!isset($data[$field])) {
//            return "{$field}必须存在";
//        }
//        return true;
//    }

    protected function isset($value, $rule, $data = [], $field = "") {
        if(!isset($data[$field])) {
            return "{$field}必须存在";
        }
        return true;
    }


    // 验证手机号码
    public function mobile($value, $rule, $data, $field="") {
        /**
        移动：134、135、136、137、138、139、150、151、152、157、158、159、182、183、184、187、188、178(4G)、147(上网卡)；
        联通：130、131、132、155、156、185、186、176(4G)、145(上网卡)；
        电信：133、153、180、181、189 、177(4G)；
         */
        if (!is_numeric($value)) {
            return "{$field} 不是有效的手机号码";
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $value) ? true : "{$field} 不是有效的手机号码";
    }

    // 格式字符串
    protected function f_trim($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = trim($value);
        return true;
    }

    // 格式整数
    protected function f_int($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = intval($value);
        return true;
    }

    // 格式浮点数
    protected function f_float($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = floatval($value);
        return true;
    }

    // 格式布尔
    protected function f_bool($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = boolval($value);
        return true;
    }

    // 转换成数组
    protected function f_array($value, $rule, $data=[], $field="") {
        $value = is_array($value) ? $value : [];
        foreach ($value as $k => $v) {
            if($rule == "int") {
                $value[$k] = intval($v);
            } else if($rule == "float") {
                $value[$k] = floatval($v);
            } else if($rule == "bool") {
                $value[$k] = boolval($v);
            } else {
                $value[$k] = trim($v);
            }
        }
        $this->params[$this->index][$field] = $value;
        return true;
    }

    // 字符串分割
    protected function f_explode($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = explode($rule, is_string($value) ? $value : "");
        return true;
    }

    // 数组合并
    protected function f_implode($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = implode($rule, is_array($value) ? $value : []);
        return true;
    }

    // Json字符串 序列化
    protected function f_json_e($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = json_encode(is_array($value) ? $value : []);
        return true;
    }

    // Json字符串 转换成数组
    protected function f_json_d($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = json_decode(is_string($value) ? $value : "[]", true);
        return true;
    }

    // Json字符串 转换成对象
    protected function f_json_o($value, $rule, $data=[], $field="") {
        $this->params[$this->index][$field] = json_decode(is_string($value) ? $value : "[]");
        return true;
    }


//    /**
//     * 验证
//     * @param string $scene
//     * @param bool $isArr
//     * @param null $params
//     * @throws ParamException
//     */
    public static function V($scene = "", $isArr = false, $params = null) {
        (new static)
            ->scene($scene)
            ->setParams($params, $isArr)
            ->checkCurrent();
    }

//    /**
//     * 验证 并 获取 Only
//     * @param string $scene
//     * @param bool $isArr
//     * @param null $params
//     * @return array
//     * @throws ParamException
//     */
    public static function VO($scene = "", $isArr = false, $params = null) {
        $value = (new static)
            ->scene($scene)
            ->setParams($params, $isArr)
            ->setAction(self::ACTION_ONLY)
            ->checkCurrent()
            ->get();
        return $value == "" ? [] : $value;
    }

//    /**
//     * 验证 并 获取 Many
//     * @param string $scene
//     * @param bool $isArr
//     * @param null $params
//     * @return array
//     * @throws ParamException
//     */
    public static function VM($scene = "", $isArr = false, $params = null) {
        $value = (new static)
            ->scene($scene)
            ->setParams($params, $isArr)
            ->setAction(self::ACTION_MANY)
            ->checkCurrent()
            ->get();
        return $value == "" ? [] : $value;
    }

//    /**
//     * 验证 并 获取 Many 以数组形式放回
//     * @param string $scene
//     * @param bool $isArr
//     * @param null $params
//     * @return array
//     * @throws ParamException
//     */
    public static function VMA($scene = "", $isArr = false, $params = null) {
        $value = (new static)
            ->scene($scene)
            ->setParams($params, $isArr)
            ->setAction(self::ACTION_ARR_MANY)
            ->checkCurrent()
            ->get();
        return $value == "" ? [] : $value;
    }

    // 主节点返回所有，子节点按传递返回，以数组形式返回
    public static function VMOA($scene = "", $isArr = false, $params = null) {
        $value = (new static)
            ->scene($scene)
            ->setParams($params, $isArr)
            ->setAction(self::ACTION_ARR_MANY)
            ->setNodeAction(self::ACTION_ONLY)
            ->checkCurrent()
            ->get();
        return $value == "" ? [] : $value;
    }

}
