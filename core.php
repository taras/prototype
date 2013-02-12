<?php
class ScaleUp {

  private static $_this;

  static $feature_types = array(
    'site' => array(
      '__CLASS__' => 'ScaleUp_Site',
      '_feature_type' => 'site',
      '_plural' => 'sites',
      '_supports' => array( 'apps', 'addons', 'views', 'forms' ),
      '_duck_types' => array( 'routable' ),
    ),
    'app' => array(
      '__CLASS__' => 'ScaleUp_App',
      '_feature_type' => 'app',
      '_plural' => 'apps',
      '_supports' => array( 'addons', 'views', 'forms' ),
      '_duck_types' => array( 'routable', 'contextual' ),
    ),
    'addon' => array(
      '__CLASS__' => 'ScaleUp_Addon',
      '_feature_type' => 'addon',
      '_plural' => 'addons',
      '_supports' => array( 'views', 'forms' ),
      '_duck_types' => array( 'routable', 'contextual' ),
    ),
    'view' => array(
      '__CLASS__' => 'ScaleUp_View',
      '_feature_type' => 'view',
      '_plural' => 'views',
      '_supports' => array( 'forms' ),
      '_duck_types' => array( 'routable', 'contextual' ),
    ),
    'form' => array(
      '__CLASS__' => 'ScaleUp_Form',
      '_feature_type' => 'form',
      '_plural' => 'forms',
    ),
  );
  static $duck_types = array( 'routable', 'contextual' );

  var $site;

  function __construct() {

    if ( isset( self::$_this ) ) {
      return new WP_Error( 'instantiation-error', 'ScaleUp class is a singleton and can only be instantiated once.' );
    } else {
      self::$_this = $this;
      $this->site  = new ScaleUp_Site( array( 'name' => 'WordPress' ) );
    }

  }

  static function this() {
    return self::$_this;
  }

  /**
   * Return feature type args matching $key and $value.
   * $key can be __CLASS__, _plural or _feature_type
   *
   * @param $key
   * @param $value
   * @return null
   */
  static function get_feature_type( $key, $value ) {

    foreach ( self::$feature_types as $feature_type => $args ) {
      if ( isset( $args[ $key ] ) && $args[ $key ] == $value ) {
        return $args;
      }
    }

    return null;
  }

  static function register( $feature_type, $args ) {
    self::$_this->site->register( $feature_type, $args );
  }

  static function activate( $feature_type, $name, $args = array() ) {
    return self::$_this->site->activate( $feature_type, $name, $args );
  }

}


class ScaleUp_Base extends stdClass {

  function __construct( $args = array() ) {

    $args = wp_parse_args( $args, $this->get_defaults() );

    foreach ( $args as $key => $value ) {
      $this->set( $key, $value );
      unset( $value );
    }
    $this->set( 'args', $args );

    $this->initialize();

  }

  public function __call( $method, $args = array() ) {
    if ( isset( $this->$method ) && is_callable( $this->$method ) ) {
      $func = $this->$method;

      return $func( $args, $this );
    }
  }

  function get_defaults() {
    return array();
  }

  /**
   * Overload this function in child class to execute code once during instantiation of this object.
   * This is a good place to execute register_* functions and hook to filters and actions.
   */
  function initialize() {
    // overload this function in child class
  }

  function load( $args ) {
    foreach ( $args as $property => $value ) {
      $this->set( $property, $value );
      unset( $value );
    }
  }

  /**
   * Return a property value
   *
   * @param $name
   * @return mixed|null
   */
  function get( $name ) {
    $property_name = "_$name";
    if ( property_exists( $this, $property_name ) ) {
      return $this->$property_name;
    }

    return null;
  }

  /**
   * Set a property value
   *
   * @param $name
   * @param $value
   */
  function set( $name, $value ) {
    $property_name        = "_$name";
    $this->$property_name = $value;
  }

  function has( $name ) {
    $property_name = "_$name";

    return isset( $this->$property_name );
  }

}

class ScaleUp_Feature extends ScaleUp_Base {

  var $_features;

  function __construct( $args ) {
    parent::__construct( $args );

    $this->_features = new ScaleUp_Base();

    $feature_type = $this->get( '_feature_type' );
    $this->load( wp_parse_args( $args, ScaleUp::$feature_types[ $feature_type ] ) );

    if ( $this->is( 'contextual' ) && $this->has( 'context' ) && !is_null( $this->get( 'context' ) ) ) {
      $context = $this->get( 'context' );
    } else {
      $scaleup = ScaleUp::this();
      $context = $scaleup->site;
    }

    // this registers and activates features when developer instantiates a feature with new keyword
    // _activated args is used to prevent automatic registration and activation of features that are instantiated via
    // activate method
    if ( is_object( $context ) && !$context->is_registered( $feature_type, $this ) && !isset( $args[ '_activated' ] ) ) {
      $context->register( $feature_type, $this );
      $context->activate( $feature_type, $this );
    }
  }

  /**
   * Return true if instance is of specified duck type
   *
   * @param $duck_type
   * @return bool
   */
  function is( $duck_type ) {
    if ( $this->has( '_duck_types' ) ) {
      $duck_types = $this->get( '_duck_types' );

      return in_array( $duck_type, $duck_types );
    }

    return false;
  }

  /**
   * Check if a feature is registered. Feature can be an object, an array or a string.
   *
   * @param $feature_type
   * @param $feature ScaleUp_Feature|array|string
   * @return bool
   */
  function is_registered( $feature_type, $feature ) {
    return !is_null( $this->get_feature( $feature_type, $feature ) );
  }

  /**
   * Return feature
   *
   * @param $feature_type
   * @param $feature
   * @return null|ScaleUp_Feature
   */
  function get_feature( $feature_type, $feature ) {

    if ( is_object( $feature ) && method_exists( $feature, 'get' ) ) {
      $name = $feature->get( 'name' );
    } else {
      if ( is_array( $feature ) ) {
        if ( isset( $feature[ 'name' ] ) ) {
          $name = $feature[ 'name' ];
        }
      } else {
        if ( is_string( $feature ) ) {
          $name = $feature;
        } else {
          return null;
        }
      }
    }

    // make sure that its a known feature
    if ( isset( ScaleUp::$feature_types[ $feature_type ] ) ) {
      $plural = ScaleUp::$feature_types[ $feature_type ][ '_plural' ];
    } else {
      return null;
    }

    if ( $this->_features->has( $plural ) ) {
      $storage = $this->_features->get( $plural );

      return $storage->get( $name );
    }

    return null;
  }

  /**
   * Register feature and return complete arguments array for this feature.
   * Registration is storing of feature's configuration array without instantiation.
   *
   * @param $feature_type
   * @param $args
   * @return array|WP_Error
   */
  function register( $feature_type, $args ) {

    // make sure that its known feature
    if ( isset( ScaleUp::$feature_types[ $feature_type ] ) ) {

      $plural = ScaleUp::$feature_types[ $feature_type ][ '_plural' ];

      if ( !$this->_features->has( $plural ) ) {
        $this->_features->set( $plural, new ScaleUp_Base() );
      }

      $storage = $this->_features->get( $plural );

      $name = null;

      if ( is_array( $args ) ) {
        $args = wp_parse_args( $args, ScaleUp::$feature_types[ $feature_type ] );
        if ( isset( $args[ 'name' ] ) ) {
          $name = $args[ 'name' ];
        }
      } elseif ( is_object( $args ) ) {
        $name = $args->get( 'name' );
      }

      /**
       * @todo: Add validation here. Validation should happen based on '_requires' argument in $_feature_types declaration
       */
      if ( is_null( $name ) ) {
        return new WP_Error( 'name-missing', __( 'Feature name is required.' ) );
      }

      $storage->set( $name, $args );

    } else {
      return new WP_Error( 'invalid-feature-type', sprintf( __( '%s is not a valid feature type.' ), $feature_type ) );
    }

    return $args;
  }

  /**
   * Activation = Instantiation
   *
   * During activation, the object is populated with all its duck types and abilities that it supports.
   *
   * @see http://en.wikipedia.org/wiki/Duck_typing#In_PHP Duck Types in PHP
   * Duck Types are methods that are added to the object to match its abilities. duck types are specified via
   * _duck_types argument which takes an array of its types. Currently, the code supports 2 abilities: routable & contextual.
   * a "routable" object can be accesed via a url and has a get_url method which returns url of the object.
   * a "contextual" object can be nested inside of another object in which case it has a "context" property which points
   * the object's container.
   *
   * Support array specifies what kind of features this object supports. For example, an instance of Site supports Apps.
   * Futher, an App supports Addons, Views & Forms. This makes it possible to programmatically activate features of an
   * object. This activation happens recursively making it possible to instantiate deeply nested features.
   *
   * @param $feature_type
   * @param $feature
   * @param array $args
   * @return ScaleUp_Feature|WP_Error
   */
  function activate( $feature_type, $feature, $args = array() ) {

    $object = null;

    // make sure that feature type is available
    if ( isset( ScaleUp::$feature_types[ $feature_type ] ) ) {
      // get plural name
      $plural = ScaleUp::$feature_types[ $feature_type ][ '_plural' ];

      // create new feature container
      if ( !$this->_features->has( $plural ) ) {
        $this->_features->set( $plural, new ScaleUp_Base() );
      }

      // convinient object
      $storage = $this->_features->get( $plural );

      if ( is_object( $feature ) ) { // feature was already instantiate it, we just need to store a reference to it in our internal storage
        if ( method_exists( $feature, 'has' ) && $feature->has( 'name' ) ) {
          $storage->set( $feature->get( 'name' ), $feature );
          $object = $feature;
        }
      } elseif ( is_array( $feature ) ) {
        if ( isset( $feature[ '__CLASS__' ] ) ) {
          $class = $feature[ '__CLASS__' ];
          if ( class_exists( $class ) ) {
            // set _activated to true to prevent automatical activation in Feature __construct
            $feature[ '_activated' ] = true;
            $object = new $class( $feature );
          } else {
            return new WP_Error( 'activation-failed', sprintf( __( '%s class does not exist.' ), $class ) );
          }
        }
      } elseif ( is_string( $feature ) ) { // activation by feature name
        if ( $storage->has( $feature ) ) { // check that feature has been registered and has arguments
          $object = $storage->get( $feature );
          if ( is_array( $object ) ) {
            $args[ 'name' ] = $feature;
            $args           = wp_parse_args( $object, $args );
            if ( isset( $args[ '__CLASS__' ] ) ) {
              $class = $args[ '__CLASS__' ];
              if ( class_exists( $class ) ) {
                // set _activated to true to prevent automatical activation in Feature __construct
                $args[ '_activated' ] = true;
                $object = new $class( $args );
              } else {
                return new WP_Error( 'activation-failed', sprintf( __( '%s class does not exist.' ), $class ) );
              }
            }
          } elseif ( is_object( $object ) ) {
            // do nothing
          } else {
            return new WP_Error( 'activation-failed', __( 'Registered feature could not be activated because arguments are not an array or object.' ) );
          }
        }
      }
    }
    if ( is_object( $object ) ) {
      $object->add_support();
      if ( $object->is( 'contextual' ) ) {
        $object->set( 'context', $this );
      }
    }

    return $object;
  }

  function add_support() {
    if ( $this->has( '_supports' ) && is_array( $this->get( '_supports' ) ) ) {
      $plural_feature_types = $this->get( '_supports' );
      foreach ( $plural_feature_types as $plural_feature_type ) {
        if ( $this->has( $plural_feature_type ) && is_array( $this->get( $plural_feature_type ) ) ) {
          $features          = $this->get( $plural_feature_type );
          $feature_type_args = ScaleUp::get_feature_type( '_plural', $plural_feature_type );
          if ( is_array( $feature_type_args ) ) {
            $feature_type = $feature_type_args[ '_feature_type' ];
            foreach ( $features as $key => $value ) {
              if ( is_numeric( $key ) ) {
                $feature_name = $value;
                $args         = array();
              } else {
                $feature_name = $key;
                if ( is_array( $value ) ) {
                  $args = $value;
                } else {
                  /**
                   * @todo: I don't think this scenario would happen because you can't have arguments as 1 element. ME think!
                   */
                  $args = array( $value );
                }
              }
              if ( $this->is_registered( $feature_type, $feature_name ) ) {
                $feature = $this->get_feature( $feature_type, $feature_name );
              } else {
                $scaleup = ScaleUp::this();
                $site    = $scaleup->site;
                if ( $site->is_registered( $feature_type, $feature_name ) ) {
                  $feature = $site->get_feature( $feature_type, $feature_name );
                } else {
                  $feature = $this->register( $feature_type, $args );
                }
              }
              if ( isset( $feature ) ) {
                if ( is_object( $feature ) && method_exists( $feature, 'get_defaults' ) ) {
                  $defaults = $feature->get_defaults();
                } else {
                  $defaults = $feature;
                }
                $args = wp_parse_args( $args, $defaults );
                if ( $this->is_registered( $feature_type, $feature_name ) ) {
                  $object = $this->activate( $feature_type, $args );
                } else {
                  $this->register( $feature_type, $args );
                  $object = $this->activate( $feature_type, $feature_name );
                }
                $features = $this->get( 'features' );
                $storage = $features->get( $plural_feature_type );
                $storage->set( $feature_name, $object );
              }
            }
          }
        }
      }
    }
  }

  function get_url( $args = array() ) {
    /**
     * @todo: apply args to url template
     */
    if ( $this->is( 'contextual' ) && $this->has( 'context' ) && !is_null( $this->get( 'context' ) ) ) {
      $context = $this->get( 'context' );
      if ( $context->is( 'routable' ) ) {
        return $context->get_url() . '/' . $this->_url;
      }
    }
    if ( property_exists( $this, '_url' ) ) {
      return $this->_url;
    }

    return null;
  }

}

class ScaleUp_Site extends ScaleUp_Feature {

  private static $_this;

  function this() {
    return self::$_this;
  }

  function __construct( $args ) {

    if ( isset( self::$_this ) ) {
      return new WP_Error( 'instantiation-error', sprintf( __( '%s class is a singleton and can not be initialized twice.' ), __CLASS__ ) );
    }
    parent::__construct( $args );

  }

  function get_defaults() {
    return wp_parse_args(
      array(
           '_feature_type' => 'site',
      ), parent::get_defaults() );
  }

}

class ScaleUp_Addon extends ScaleUp_Feature {

}

class ScaleUp_App extends ScaleUp_Feature {
  function __construct( $args ) {
    parent::__construct( $args );
  }

  function get_defaults() {
    return wp_parse_args(
      array(
           '_feature_type' => 'app',
      ), parent::get_defaults() );
  }
}

class ScaleUp_View extends ScaleUp_Feature {
  function get_defaults() {
    return wp_parse_args(
      array(
           '_feature_type' => 'view',
      ), parent::get_defaults() );
  }
}

class ScaleUp_Form extends ScaleUp_Feature {
  function get_defaults() {
    return wp_parse_args(
      array(
           '_feature_type' => 'form',
      ), parent::get_defaults()
    );
  }
}

new ScaleUp();

?>
<pre>

</pre>