<?php
class ScaleUp_Login_Addon extends ScaleUp_Addon {

  function get_defaults() {
    return wp_parse_args(array(
      'views' => array(
        'login' => array(
          'name'      => 'login',
          'url'       => 'login',
          'callbacks' => array(
            'GET'       => array( $this, 'show_views' ),
            'POST'      => array( $this, 'process_submission' ),
          ),
        ),
      ),
      'forms' => array(
        'login'       => array(
          'name'        => 'login',
          'title'       => __( 'Login' ),
          'fields'      => array(
            array(
              'name'        => 'username',
              'type'        => 'text',
              'validation'  => array( 'required' ),
            ),
            array(
              'name'        => 'password',
              'type'        => 'password',
              'validation'  => array( 'required' ),
            ),
          ),
        ),
        'register'    => array(
          'name'        => 'register',
          'title'       => __( 'Register' ),
          'fields'      => array(
            array(
              'name'        => 'givenName',
              'type'        => 'text',
              'validation'  => array( 'required' ),
            ),
            array(
              'name'        => 'familyName',
              'type'        => 'text',
              'validation'  => array( 'required' ),
            )
          ),
        ),
      ),
    ), parent::get_defaults());
  }

  function initialize() {

  }

}
ScaleUp::register( 'addon', array( 'name' => 'login', '__CLASS__' => 'ScaleUp_Login_Addon' ) );