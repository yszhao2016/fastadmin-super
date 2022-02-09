<?php
namespace app\hj212\model;

/**
 * 数据段 标志
 * Class DataFlag
 * @package App\Model
 */
class DataFlag {
//    @ApiModelProperty(value = "命令是否应答")
//    A(1),
//    @ApiModelProperty(value = "是否有数据包序号")
//    D(2),
//    @ApiModelProperty(value = "标准版本号V0（HJ 212-2017）")
//    V0,
//    @ApiModelProperty(value = "标准版本号V1")
//    V1,
//    @ApiModelProperty(value = "标准版本号V2")
//    V2,
//    @ApiModelProperty(value = "标准版本号V3")
//    V3,
//    @ApiModelProperty(value = "标准版本号V4")
//    V4,
//    @ApiModelProperty(value = "标准版本号V5")
//    V5;

    private $bit;

//    function DataFlag(){
//        $this->bit = (1 << ordinal());
//    }

    function DataFlag($bit){
        $this->bit = $bit;
    }

    function getBit() {
        return $this->bit;
    }

    function isMarked($flags) {
        return ($flags & $bit) != 0;
    }

//    function isMarked(DataFlag $flags) {
//        return flags != null && flags.contains(this);
//    }

}
