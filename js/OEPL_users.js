/*jshint esversion: 6 */
/* global jQuery, objusertocrm, Swal*/
/* exported oepl_dialogbox */
(function($) {
    "use strict";
 	$(function() {
		function oepl_dialogbox(message){
			Swal.fire({
				title: 'Success!',
				text: message,
				icon: 'success',
				timer: 2000,
				allowOutsideClick: false,
				showConfirmButton: false,
			});
		}

		$(document).ready(function($) {
			var field = $('.submit_to_crm');
			$('.submit_to_crm').remove();
			field.appendTo('.submit');
			
			$(".submit_to_crm").on("click", function() {
				var data = {};
				data.action = 'OEPL_SaveWPUserToCRM_Contactsmodule';
				data.user_id = objusertocrm.user_id;
				var bgcolor = $(".button-primary").css("background-color");
				
				$('.oe-loader-section').show();
				$.post(objusertocrm.ajaxurl,data,function(response) {
					$('.oe-loader-section').hide();
					if (response.status === 'Y') {
						oepl_dialogbox(response.message);
						
					} else if (response.status === 'confirm') {

						Swal.fire({
							title: response.message,
							showDenyButton: true,
							showCancelButton: true,
							confirmButtonColor: bgcolor,
							confirmButtonText: 'Crate New',
							denyButtonText: 'Update Existing',
							denyButtonColor: bgcolor,
						  }).then((result) => {
							var data = {};
							if (result.isConfirmed) {
								// Create new contact
								$(".ui-dialog-titlebar-close").trigger("click");
									data.action = 'OEPLCreatNewWPUserToCRMContacts';
									data.user_id = objusertocrm.user_id;
									$.post(objusertocrm.ajaxurl,data,function(response){
										if (response.status === 'Y') {
											oepl_dialogbox(response.message);
										}
									});
							} else if (result.isDenied) {
								// Update existing contact
								$(".ui-dialog-titlebar-close").trigger("click");
								
								data.action = 'OEPLUpdateExistingWPUserToCRMContacts';
								data.user_id = objusertocrm.user_id;
								
								$.post(objusertocrm.ajaxurl,data,function(response) {
									if (response.status === 'Y') {
										oepl_dialogbox(response.message);
									}
								});
							}
						  });
					} else if(response.status === 'record selection') {

						Swal.fire({
							title: 'Duplicate E-mail Found',
							input: 'radio',
							inputOptions: response.duplicate_contact,
							confirmButtonText: 'Submit',
							inputValidator: function(result) {
							  if (!result) {
								return 'Please choose atleast one contact.';
							  }
							}
							}).then(function(result) {
							if (result.isConfirmed) {
								$(".ui-dialog-titlebar-close").trigger("click");
								if(result.value == undefined){
									alert("Please choose atleast one contact.");
									return;	
								}
								var data = {};
								data.action = 'OEPLSaveUserToCRMContacts';
								data.user_id = objusertocrm.user_id;
								data.record_id = result.value;
								
								$.post(objusertocrm.ajaxurl,data,function(response) {
									if (response.status === 'Y') {
										oepl_dialogbox(response.message);
									}	 
								});
							}
						});
					}
				});
			});
		});
	});
})(jQuery);