<?php

/**
 * Class Widget_Overview
 *
 * @author Elvis Chen
 */
class Widget_Overview extends MY_Widget {
    public function initialize($options = []){
        if(!$this->load->get_var('isLogged')){
            return false;
        }
    }
}