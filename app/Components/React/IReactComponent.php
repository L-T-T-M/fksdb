<?php

namespace FKSDB\Components\React;


interface IReactComponent {
    /**
     * @return string
     */
    function getComponentName(): string;

    /**
     * @return string
     */
    function getModuleName(): string;

    /**
     * @return string
     */
    function getMode(): string;

    /**
     * @return string
     */
    function getData(): string;
}
