<?php

namespace Cherry\Core;

interface IObjectManagerInterface {
    public function omiGetObjectList($path);
    public function omiGetObject($path);
    public function omiGetObjectProperties($path);
}

