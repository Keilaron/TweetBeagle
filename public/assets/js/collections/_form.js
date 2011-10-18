$(document).ready(function() {

  changeReferenceField();

  // Determine which reference field to show based on the seletec collection type
  $('#collection-type').change(changeReferenceField);

});

/**
 *
 */
function changeReferenceField() {
  var type = $('#collection-type').val();
  $('.reference-field').hide();
  $('#collection-reference-' + type).show();
}