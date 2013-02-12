<?php
class Login_Addon extends Addon {

  function get_defaults() {
    return array(
      'views' => array(
        array(
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
    );
  }

  function initialize() {

  }

}
ScaleUp::register( 'addon', array( 'name' => 'login', '__CLASS__' => 'Login_Addon' ) );