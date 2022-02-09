<?php
namespace app\hj212\feature;

use app\hj212\segment\base\Feature;

/**
 * 解析特性
 * Class ParserFeature
 * @package App\core\feature
 */
//class ParserFeature implements Feature {
class ParserFeature {

    /**
     * 头常量
     */
    const HEADER_CONSTANT = true;

    /**
     * 尾常量
     */
    const FOOTER_CONSTANT = false;


    private $_defaultState;
    private $_mask;

    function __construct($defaultState = 8) {
        $this->_defaultState = $defaultState;
        $this->_mask = (1 << 0);
//        $this->_mask = (1 << ordinal());
    }

    function enabledByDefault() {
        return $this->_defaultState;
    }

    function getMask() {
        return $this->_mask;
    }

    function enabledIn($flags) {
        return ($flags & $this->_mask) != 0;
    }

}
