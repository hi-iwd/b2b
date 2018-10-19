define([
    'jquery',
	'jquery/ui',
    "domReady"
], function ($j, domReady) {
  'use strict';

  $j.widget('iwd.b2b', {
      _create: function () {
          var widget = this;

          $j(document).ready(function(){
              var b2bTablesUrl = '';

              if(typeof(widget.options.b2bTablesUrl) != 'undefined' && widget.options.b2bTablesUrl != undefined && widget.options.b2bTablesUrl)
                  b2bTablesUrl = widget.options.b2bTablesUrl;

              window.IWDB2B.Tables.b2bTablesUrl = b2bTablesUrl;

              window.IWDB2B.App.init();
          });

      },
  });

  window.IWDB2B = window.IWDB2B||{};

  window.IWDB2B.App = {

        init: function(){
            window.IWDB2B.File.init();
            window.IWDB2B.Tables.init();
        }

  };

  window.IWDB2B.File = {
        init: function(){
            window.IWDB2B.File.checkMode();
            window.IWDB2B.File.checkTypeFile();

            $j('#edit_form #mode').change(function(e){
                window.IWDB2B.File.checkMode();
            });

            $j('#edit_form #is_active_file_or_url').change(function(e){
               window.IWDB2B.File.checkTypeFile();
            });

        },

        checkMode: function(){
            if($j('#edit_form #mode').val() == 2){
                $j('#attribute-category_ids-container').show();
                $j('#category_ids').addClass('required-entry');
                $j('#category_ids').addClass('_required');
            }
            else{
                $j('#attribute-category_ids-container').hide();
                $j('#category_ids').removeClass('required-entry');
                $j('#category_ids').removeClass('_required');
            }
        },

        checkTypeFile: function(){

            if($j('#edit_form #is_active_file_or_url').val() == 2){
                $j('.field-download_url_path').show();
                $j('.field-name').hide();

                $j('.field-name #name').removeClass('required-entry');
                $j('.field-name #name').removeClass('_required');

                $j('.field-download_url_path #download_url_path').addClass('required-entry');
                $j('.field-download_url_path #download_url_path').addClass('_required');
            }
            else{
                $j('.field-download_url_path').hide();
                $j('.field-name').show();

                $j('.field-download_url_path #download_url_path').removeClass('required-entry');
                $j('.field-download_url_path #download_url_path').removeClass('_required');

                if(!$j('.field-name #current_file_link').length){
                    $j('.field-name #name').addClass('required-entry');
                    $j('.field-name #name').addClass('_required');
                }
                else{
                    $j('.field-name #name').removeClass('required-entry');
                    $j('.field-name #name').removeClass('_required');
                }
            }
        },

  };

  window.IWDB2B.Tables = {

          b2bTablesUrl: false,

          init: function(){
              $j(document).on('change', '#table_name', function(e){
                  e.preventDefault();
                  window.IWDB2B.Tables.load( $j(this).val() );
              });

              window.IWDB2B.Tables.initSortable();
          },

          initSortable: function(){
              $j( "#sortable1, #sortable2" ).sortable({
                  connectWith: ".connectedSortable"
              }).disableSelection();
          },

          load: function(table){
              $j.post(window.IWDB2B.Tables.b2bTablesUrl, {"table":table}, function(response){
                  if (response.content!="undefined"){
                      $j('#b2b_table_columns').html(response.content);

                      window.IWDB2B.Tables.initSortable();

                      init_b2b_grid_form($j);
                  }
              }, 'json');
          }

  };

  var object = $j("span:contains('disabled')");
  if(object != null) {
    //disables Disabled Payment methods
    $j(object).parent().siblings().attr('disabled', true);
    $j(object).parent().siblings().attr('checked', false);
    $j(object).parent().parent().css('display', 'none');
    $j("span:contains('B2B Requester Payment')").parent().parent().css('display', 'none');
  }

  
  //Check all payment method boxes by default unless the company already exists
  //(so we only target new companies and don't overwrite existing settings)
  /////////////////////////////////////////////////////////////////////////////
  object = $j(".field-payments .control span:not(:contains('disabled'))");
  if ((object != null) && (document.getElementById('store_name') != null)) {
      if (document.getElementById('store_name').value == "" ) {
          $j(object).parent().siblings().attr('checked', true);
      }
  }

  // SHOW/HIDE CREDIT-RELATED fields
  //////////////////////////////////////////////////////////////////////////////
  if (document.querySelector('#active_limit') != null){
      // Configure displays for credit-related fields according to the saved value
      ///////////////////////////////////////////////////////////////////////////
      function showOrHideCreditLimitFields () {
          //If 'No' is selected, hide credit-related fields
          if (document.querySelector('#active_limit').value == 0) {
              //Seek and destroy Credit Limit Field
              $j(".field-credit_limit").css('display', 'none');

              //Seek and destroy Available Credit Field
              $j(".field-available_credit").css('display', 'none');
          }
          //Else, the value is 'Yes' and we should display these
          else {
              //Restore Credit Limit Field
              $j(".field-credit_limit").css('display', 'block');

              //Restore Available Credit Field
              $j(".field-available_credit").css('display', 'block');
          }
      }

      //Event listener for changes to select#active_limit's value
      ///////////////////////////////////////////////////////////
      document.querySelector('#active_limit').addEventListener('change', function() {showOrHideCreditLimitFields();}, false);

      //Call to make sure things are displayed correctly according to saved value
      showOrHideCreditLimitFields ();
  }


  return $j.mage.b2b;

});
