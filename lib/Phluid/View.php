<?php
/**
 * Represents HTML or any content for that matter to be rendered as the body for a response
 * It needs to know the template name as well as the directory the templates live in. When
 * rendering it starts a new output buffer, includes the template, stores the buffer and 
 * cleans it. This way we can allow layouts to nest view inside them.
 * 
 * A layout is simply a view that has an `echo $content` somewhere in it.
 */

require_once 'Exceptions.php';

class Phluid_View {
    
  private $template;
  private $path;
  private $layout;
  
  function __construct( $template, $layout = null, $path = null ){
    $this->template = $template;
    $this->layout = $layout;
    $this->path = $path;
  }
  
  /**
   * include a PHP file with the given locals as variables available to it
   */
  public function render( $locals = array() ){
    
    extract($locals);
    
    ob_start();
    $included = @include $this->fullPath() ;
    $content = ob_get_clean();
    
    if( $included === false )
      throw new Phluid_Exception_MissingView( "Missing template " . $this->fullPath() );
    
    if ( $layout = $this->getLayout() ) {
      $content = $layout->render( array_merge( $locals, array( 'content' => $content ) ) );
    }
    
    return $content;
    
  }
  
  public function getLayout(){
    if ( $this->layout ) {
      return new Phluid_View( $this->layout, null, $this->path );
    }
  }
  
  public function hasLayout(){
    return $this->layout != null || self::$layout != null;
  }
  
  public function fullPath(){
    return $this->path . '/' . $this->template . '.php';
    
  }
  
}

class Phluid_Exception_MissingView extends Phluid_Exception {
  function __construct( $message ){
    parent::__construct( $message, 404 );
  }
}