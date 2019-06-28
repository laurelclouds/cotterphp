<?php
return function($class) {
    if(DIRECTORY_SEPARATOR!=="\\") $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $class . ".php";
};
?>