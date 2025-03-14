<?php
/**
 * player_main_js_generator.php
 *
 * @author Elvis Chen
 */

class Player_main_js_generator {
    /* @var BaseController */
    public $CI;

    /* @var lib_minify */
    protected $_lib_minify;

    protected $_content;
    protected $_content_min;

    protected $_content_css;
    protected $_content_css_min;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->library('minify/lib_minify');

        $this->_lib_minify = $this->CI->lib_minify;

        $this->_content = '';
        $this->_content_min = '';
        $this->_content_css = '';
        $this->_content_css_min = '';
    }

    public function append($content, $minify = TRUE){
        $this->_content .= $content . ";\r\n";
        $this->_content_min .= ($minify) ? $this->_lib_minify->minifyJS($content) . ";\r\n" : $content . ";\r\n";
    }

    public function appendCSS($content, $minify = TRUE){
        $this->_content_css .= $content . ";\r\n";
        $this->_content_css_min .= ($minify) ? $this->_lib_minify->minifyCSS($content) . ";\r\n" : $content . ";\r\n";
    }

    public function appendFile($file_path, $minify = TRUE){
        if(file_exists($file_path)){
            $this->append(file_get_contents($file_path), $minify);
        }else{
            $this->CI->utils->debug_log(__CLASS__ . "::appendFile(): The {$file_path} File not exists!");
        }
    }

    public function appendCSSFile($file_path, $minify = TRUE){
        if(file_exists($file_path)){
            $this->appendCSS(file_get_contents($file_path), $minify);
        }else{
            $this->CI->utils->debug_log(__CLASS__ . "::appendFile(): The {$file_path} File not exists!");
        }
    }

    public function directlyOutput2BuiltIn($file_path, $output_path, $output_full_filename = null){
        if(file_exists($file_path)){
            $content = file_get_contents($file_path);
        }else{
            $content = '';
            $this->CI->utils->debug_log(__CLASS__ . "::directlyOutput2BuiltIn(): The {$file_path} File not exists!");
        }

        if( empty($output_full_filename) ){
            $output_full_filename = basename($file_path);
        }

        $final_content = null;
        $is_content_empty = empty($content);
        if( ! $is_content_empty ){
            $today = $this->CI->utils->getDatetimeNow();
            $cms_version = $this->CI->utils->getCmsVersion();

            $final_content = <<<EOF
    <!-- // last build at {$today} v{$cms_version} -->
    $content
EOF;
            $output_normal_path = $output_path . DIRECTORY_SEPARATOR . $output_full_filename;
            file_put_contents($output_normal_path, $final_content);
            @chmod($output_normal_path, 0777);
        }
        return $final_content;
    }
    /**
     * Create the (min) Javascript, (min) CSS file by the attr.,"_content".
     *
     * @param string $output_path
     * @param string $output_filename
     * @param boolean $is_content_wrapped Only for Javascript.  If its false then the prefix,"(function(){" and suffix,"})();" will wrap the content.
     * @return void
     */
    public function createOutput($output_path, $output_filename, $is_content_wrapped = false){
        $content = $this->_content;
        $content_min = $this->_content_min;

        $content_css = $this->_content_css;
        $content_css_min = $this->_content_css_min;

        $today = $this->CI->utils->getDatetimeNow();
        $cms_version = $this->CI->utils->getCmsVersion();

        $is_content_empty = empty($content);
        $is_content_min_empty = empty($content_min);
        $is_content_css_empty = empty($content_css);
        $is_content_css_min_empty = empty($content_css_min);

        if( ! $is_content_empty && !$is_content_wrapped) {
            $final_content = <<<JAVASCRIPT
(function(){
    // last build at {$today} v{$cms_version}
    $content
})();
JAVASCRIPT;
        }else{
            $final_content = <<<JAVASCRIPT
    // last build at {$today} v{$cms_version}
    $content
JAVASCRIPT;
        }

        if( ! $is_content_min_empty && !$is_content_wrapped) {
            $final_content_min = <<<JAVASCRIPT
(function(){
    // last build at {$today} v{$cms_version}
    $content_min
})();
JAVASCRIPT;
        }else{
            $final_content_min = <<<JAVASCRIPT
    // last build at {$today} v{$cms_version}
    $content_min
JAVASCRIPT;
        }

        if( ! $is_content_css_empty) {
            $final_content_css = <<<CSS
@charset "UTF-8";
/* last build at {$today} v{$cms_version} */

$content_css
CSS;
        }


        if( ! $is_content_css_min_empty) {
            $final_content_css_min = <<<CSS
@charset "UTF-8";
/* last build at {$today} v{$cms_version} */

$content_css_min
CSS;
        }

        $output_normal_path = $output_path . DIRECTORY_SEPARATOR . $output_filename . '.js';
        $output_minify_path = $output_path . DIRECTORY_SEPARATOR . $output_filename . '.min' . '.js';
        $output_css_normal_path = $output_path . DIRECTORY_SEPARATOR . $output_filename . '.css';
        $output_css_minify_path = $output_path . DIRECTORY_SEPARATOR . $output_filename . '.min' . '.css';

        if( ! $is_content_empty) {
            file_put_contents($output_normal_path, $final_content);
            @chmod($output_normal_path, 0777);
        }

        if( ! $is_content_min_empty) {
            file_put_contents($output_minify_path, $final_content_min);
            @chmod($output_minify_path, 0777);
        }

        if( ! $is_content_css_empty) {
            file_put_contents($output_css_normal_path, $final_content_css);
            @chmod($output_css_normal_path, 0777);
        }

        if( ! $is_content_css_min_empty) {
            file_put_contents($output_css_minify_path, $final_content_css_min);
            @chmod($output_css_minify_path, 0777);
        }
        return $final_content;
    }
}