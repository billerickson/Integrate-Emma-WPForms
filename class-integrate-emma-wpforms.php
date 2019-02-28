<?php
/**
 * Integrate Emma and WPForms
 *
 * @package    Integrate_Emma_WPForms
 * @since      1.0.0
 * @copyright  Copyright (c) 2017, Bill Erickson
 * @license    GPL-2.0+
 */

class Integrate_Emma_WPForms {

    /**
     * Primary Class Constructor
     *
     */
    public function __construct() {

        add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_section' ), 20, 2 );
        add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
        add_action( 'wpforms_process_complete', array( $this, 'send_data_to_emma' ), 10, 4 );

    }

    /**
     * Add Settings Section
     *
     */
    function settings_section( $sections, $form_data ) {
        $sections['be_emma'] = __( 'Emma', 'integrate-emma-wpforms' );
        return $sections;
    }


    /**
     * emma Settings Content
     *
     */
    function settings_section_content( $instance ) {
        echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-be_emma">';
        echo '<div class="wpforms-panel-content-section-title">' . __( 'Emma', 'integrate-emma-wpforms' ) . '</div>';

		/*
        if( empty( $instance->form_data['settings']['be_emma_api'] ) ) {
            printf(
                '<p>%s <a href="http://mbsy.co/emma/28981746" target="_blank" rel="noopener noreferrer">%s</a></p>',
                __( 'Don\'t have an account?', 'integrate-emma-wpforms' ),
                __( 'Sign up now!', 'integrate-emma-wpforms' )
            );
        }
		*/

		wpforms_panel_field(
			'text',
			'settings',
			'be_emma_account_id',
			$instance->form_data,
			__( 'Account ID', 'integrate-emma-wpforms' )
		);

        wpforms_panel_field(
            'text',
            'settings',
            'be_emma_public_key',
            $instance->form_data,
            __( 'Public Key', 'integrate-emma-wpforms' )
        );

        wpforms_panel_field(
            'text',
            'settings',
            'be_emma_private_key',
            $instance->form_data,
            __( 'Private Key', 'integrate-emma-wpforms' )
        );

		wpforms_panel_field(
			'text',
			'settings',
			'be_emma_groups',
			$instance->form_data,
			__( 'Group IDs (comma separated)', 'integrate-emma-wpforms' )
		);

        wpforms_panel_field(
            'select',
            'settings',
            'be_emma_field_email',
            $instance->form_data,
            __( 'Email Address', 'integrate-emma-wpforms' ),
            array(
                'field_map'   => array( 'email' ),
                'placeholder' => __( '-- Select Field --', 'integrate-emma-wpforms' ),
            )
        );

        wpforms_panel_field(
            'select',
            'settings',
            'be_emma_field_first_name',
            $instance->form_data,
            __( 'First Name', 'integrate-emma-wpforms' ),
            array(
                'field_map'   => array( 'text', 'name' ),
                'placeholder' => __( '-- Select Field --', 'integrate-emma-wpforms' ),
            )
        );

        wpforms_panel_field(
            'select',
            'settings',
            'be_emma_field_last_name',
            $instance->form_data,
            __( 'Last Name', 'integrate-emma-wpforms' ),
            array(
                'field_map'   => array( 'text', 'name' ),
                'placeholder' => __( '-- Select Field --', 'integrate-emma-wpforms' ),
            )
        );

        echo '</div>';
    }

    /**
     * Integrate WPForms with Emma
     *
     */
    function send_data_to_emma( $fields, $entry, $form_data, $entry_id ) {

        // Get API key and CK Form ID
        $public_key = $private_key = $account_id = false;
        if( !empty( $form_data['settings']['be_emma_public_key'] ) )
            $public_key = sanitize_key( $form_data['settings']['be_emma_public_key'] );
        if( !empty( $form_data['settings']['be_emma_private_key'] ) )
            $private_key = sanitize_key( $form_data['settings']['be_emma_private_key'] );
		if( !empty( $form_data['settings']['be_emma_account_id'] ) )
			$account_id = intval( $form_data['settings']['be_emma_account_id'] );

        if( ! ( $public_key && $private_key && $account_id ) )
            return;

		// Filter for limiting integration
		// @see https://www.billerickson.net/code/integrate-emma-wpforms-conditional-processing/
        if( ! apply_filters( 'be_emma_process_form', true, $fields, $form_data ) )
            return;

		require 'EmmaPHP/src/Emma.php';
		$emma = new Emma( $account_id, $public_key, $private_key );

        $email_field_id = $form_data['settings']['be_emma_field_email'];
        $first_name_field_id = $form_data['settings']['be_emma_field_first_name'];
		$last_name_field_id = $form_data['settings']['be_emma_field_last_name'];
		$groups = $form_data['settings']['be_emma_groups'];
		if( !empty( $groups ) )
			$groups = array_filter( array_map( 'intval', explode( ',', str_replace( ', ', ',', $groups ) ) ) );

		$member = array();
		$fields = array();
		if( !empty( $email_field_id ) && !empty( $fields[ $email_field_id ]['value'] ) )
			$member['email'] = $fields[ $email_field_id ]['value'];
		if( !empty( $first_name_field_id ) && !empty( $fields[ $first_name_field_id ]['value'] ) )
			$fields['first_name'] = $fields[ $first_name_field_id ]['value'];
		if( !empty( $last_name_field_id ) && !empty( $fields[ $last_name_field_id ]['value'] ) )
			$fields['last_name'] = $fields[ $last_name_field_id ]['value'];
		if( !empty( $fields ) )
			$member['fields'] = $fields;
		if( !empty( $groups ) )
			$member['group_ids'] = $groups;

		$req = $emma->membersAddSingle($member);

    }

}
new Integrate_Emma_WPForms;
