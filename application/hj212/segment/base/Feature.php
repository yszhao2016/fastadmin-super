<?php
namespace app\hj212\segment\base;

interface Feature {

    function enabledByDefault();

    function getMask();

    function enabledIn($flags);


//    function collectFeatureDefaults($enumClass) {
//        int $flags = 0;
//        for (F value : enumClass.getEnumConstants()) {
//            if (value.enabledByDefault()) {
//                flags |= value.getMask();
//            }
//        }
//        return flags;
//    }
}
