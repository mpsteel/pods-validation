
jQuery(document).ready(function($){

  //listen for changes on error fields
  $(document).on('change paste keyup', '.pods-validation-error-field', function() {
    if ($(this).val()) {
      $(this).removeClass('pods-validation-error-field');
      $(this).siblings('.pods-validation-error-message').fadeOut('fast');
    }
  });

  //listen for changes on pick box changes
  $(document).on('change paste keyup', '.pods-validation-error-field input[type=checkbox], .pods-validation-error-field input[type=radio]', function() {
    if ($(this).prop('checked') ) {
      $pick_box = $(this).closest('.pods-pick-values');
      $pick_box.removeClass('pods-validation-error-field');
      $pick_box.siblings('.pods-validation-error-message').fadeOut('fast');
    }
  });

  $('body.wp-admin form#post').submit(function(e){
    
    //vars
    var self = this;
    var post_type = $('#post_type').val();
    var post_id = $('#post_ID').val();
    var form_data = $( this ).serialize();
    
    //stop the form from submitting
    e.preventDefault();
    
    //make the ajax validation call
    $.post(
      ajaxurl
      ,{
        action:'pods_validation'
        ,post_id:post_id
        ,post_type:post_type
        ,form_data:form_data
      }
      ,function(data) { //success
        data = $.parseJSON(data);
        if ( Object.keys(data).length ) {
          
          //destroy all messages and remove all classes
          $('body.wp-admin form#post .pods-validation-error-message').remove();
          $('body.wp-admin form#post .pods-validation-error-field').removeClass("pods-validation-error-field");
          
          $.each(data, function(i, item) {
            selector = 'body.wp-admin form#post [data-name-clean="'+ i +'"]';
            
            $input = $(selector);
            
            //assume the error actions will be performed on the input
            $error_item = $input;
            
            //it was a checkbox or radio button field. put errors on container, not the inputs
            if ( $input.hasClass('pods-form-ui-field-type-pick') && $input.parents('.pods-pick-values').length ) {
              $error_item = $input.closest('.pods-pick-values');
            }
            
            //put the error class on the input or pick box
            $error_item.addClass('pods-validation-error-field');

            //display an error message next to the input or pick box
            if ( ! $error_item.siblings('.pods-validation-error-message').length ) 
              $error_item.after('<p class="pods-validation-error-message">'+ item +'</p>');
            
            //reset the WordPress publishing button, etc
            $('#publishing-action .spinner').hide();
            $('#publishing-action #publish').removeClass('button-primary-disabled');
            
            //scroll down to the form
            $('html, body').animate({
              scrollTop: $('#pods-meta-'+ post_type +'-options, #pods-meta-more-fields').offset().top
            }, 200);
          });
        }
        else {
          //all good!
          self.submit();
        }
      }
      //,'json'
    );
    return true;
  });
  
  
});//document.ready