<?php

namespace Phluid;

class App {
  
  private $router;
  private $middleware = array();
  private $settings;
  private $router_mounted = false;
  
  /**
   * Passes an array of settings to initialize Settings with.
   *
   * @param array $options the settings for the app
   * @return App
   * @author Beau Collins
   **/
  public function __construct( $options = array() ){
    
    $defaults = array( 'view_path' => realpath('.') . '/views' );
    $this->settings = new Settings( array_merge( $defaults, $options ) );
    $this->router = new Router();
    
  }
  
  /**
   * Retrieve a setting
   *
   * @param string $key 
   * @return mixed
   * @author Beau Collins
   */
  public function __get( $key ){
    return $this->settings->__get( $key );
  }
  
  /**
   * Set a setting
   *
   * @param string $key the setting name
   * @param mixed $value value to set
   * @author Beau Collins
   */
  public function __set( $key, $value ){
    return $this->settings->__set( $key, $value );
  }
  
  /**
   * Starts up the app and renders a response to stdout
   *
   * @return void
   * @author Beau Collins
   */
  public function run(){
    
    ob_start();
    
    $request = Request::fromServer()->withPrefix( $this->prefix );
    $response = $this->buildResponse( $request );
    
    $response = $this->serve( $request, $response );
    
    $this->sendResponseHeaders( $response );
    ob_end_clean();
    echo $response->getBody();
    
  }
    
  /**
   * Given a Request it runs the configured middlewares and routes and
   * returns the response.
   *
   * @param Request $request 
   * @return Response
   * @author Beau Collins
   */
  public function serve( $request, $response = null, $next = null ){
    if( !$response ) $response = $this->buildResponse( $request );
    // mount the router if it hasn't been mounted explicitly
    if ( $this->router_mounted === false ) $this->inject( $this->router );
    
    // get a copy of the middleware stack
    $middlewares = $this->middleware;
    if( $next ) array_push( $middlewares, $next );
    Utils::performFilters( $request, $response, $middlewares );
    
    return $response;
    
  }
  
  /**
   * An app is just a specialized middleware
   *
   * @param string $request 
   * @return void
   * @author Beau Collins
   */
  public function __invoke( $request, $response = null, $next = null ){
    return $this->serve( $request, $response, $next );
  }
  
  public function buildResponse( $request = null ){
    return new Response( $request, array(
      'view_path' => $this->view_path,
      'default_layout' => $this->default_layout
    ) );
  }
  
  /**
   * calls header for each header in Response.
   * TODO: Better suited for some kind of adapter
   *
   * @param Response $response 
   * @return void
   * @author Beau Collins
   */
  private function sendResponseHeaders( $response ){
    header( $response->statusHeader() );
    $response->eachHeader( function( $name, $value ){
      header( $name . ': ' . $value, true );
    } );
  }
  
  /**
   * Adds the given middleware to the app's middleware stack. Returns $this for
   * chainable calls.
   *
   * @param Middleware $middleware 
   * @return App
   * @author Beau Collins
   */
  public function inject( $middleware ){
    if ( $middleware === $this->router ) $this->router_mounted = true;
    array_push( $this->middleware, $middleware );
    return $this;
  }
  
  /**
   * Configures a route give the HTTP request method, calls Router::route
   * returns $this for chainable calls
   *
   * Example:
   *
   *  $app->on( 'GET', '/profile/:username', function( $req, $res, $next ){
   *    $res->renderText( "Hello {$req->param('username')}");
   *  });
   *
   * @param string $method GET, POST or other HTTP method
   * @param string $path the matching path, refer to Router::route for options
   * @param invocable $closure an invocable object/function that conforms to Middleware
   * @return App
   * @author Beau Collins
   */
  public function on( $method, $path, $filters, $action = null ){
    return $this->route( new RequestMatcher( $method, $path ), $filters, $action );
  }
  
  /**
   * Chainable call to the router's route method
   *
   * @param invocable $matcher 
   * @param invocable or array $filters 
   * @param invocable $action 
   * @return App
   * @author Beau Collins
   */
  public function route( $matcher, $filters, $action = null ){
    $this->router->route( $matcher, $filters, $action );
    return $this;
  }
  
  /**
   * Adds a route matching a "GET" request to the given $path. Returns $this so
   * it is chainable.
   *
   * @param string $path 
   * @param invocable or array $filters compatible function/invocable
   * @param invocable $closure compatible function/invocable
   * @return App
   * @author Beau Collins
   */
  public function get( $path, $filters, $action = null ){
    return $this->on( 'GET', $path, $filters, $action );
  }
  
  /**
   * Adds a route matching a "POST" request to the given $path. Returns $this so
   * it is chainable.
   *
   * @param string $path 
   * @param invocable or array $filters compatible function/invocable
   * @param invocable $closure compatible function/invocable
   * @return App
   * @author Beau Collins
   */
  public function post( $path, $filters, $action = null ){
    return $this->on( 'POST', $path, $filters, $action );
  }
  
}