<?php
return function($namespace) {
    if (DIRECTORY_SEPARATOR!=="\\") $class = str_replace("\\", DIRECTORY_SEPARATOR, $namespace);
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . $namespace;
};
