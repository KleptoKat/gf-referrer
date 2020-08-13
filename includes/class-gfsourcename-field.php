<?php

class GF_SourceName_Field extends GF_Field_Hidden {

  public $type = 'gfsourcename';

  /**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
  public function get_form_editor_field_title() {
    return esc_attr__( 'Source Name', 'gf-sourcename' );
  }

  /**
	 * Assign the field button to the Advanced Fields group.
	 *
	 * @return array
	 */
  public function get_form_editor_button() {
    return array(
      'group' => 'advanced_fields',
      'text'  => $this->get_form_editor_field_title(),
    );
  }

  /**
   * The settings that should be available on the field in the form editor.
   *
   * @return array
   */
  function get_form_editor_field_settings() {
		return array(
      'label_setting',
		);
	}

  /**
  * Override the get_value_save_entry function to set the custom field entry value
  *
  */
  public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
    if (array_key_exists(SOURCE_NAME_SESSION_NAME, $_SESSION)) {
      return $_SESSION[SOURCE_NAME_SESSION_NAME];
    } else {
      return 'Website';
    }
  }

  public function get_field_label( $force_frontend_label, $value ) {
    if (!empty($this->label) && $this->label != 'Untitled') {
      return $this->label;
    }
    $field_title = $this->get_form_editor_field_title();
    $this->label = $field_title;
    return $field_title ? $field_title : GFCommon::get_label( $this );
  }

}

GF_Fields::register( new GF_SourceName_Field() );

?>
