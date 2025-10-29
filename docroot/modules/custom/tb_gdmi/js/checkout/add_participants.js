(function ($, Drupal) {
  const messages = new Drupal.Message();

  Drupal.behaviors.participants = {
    attach: function (context, settings) {
      var wrapper = $('.person-details-wrapper', context);
      var addButton = $('<button type="button" class="text-uppercase add-person-item btn btn-blue btn-primary mx-auto d-block">+ Add Participant</button>');

      // Updating form-actions buttons.
      var updatePreviousLink = $(once('updateLinks','.form-actions', context));
      if (updatePreviousLink.length) {
        $('.form-actions .link--previous', context).each(function () {
          var newWrapper = $('<div>', {
            'class': 'link--previous-wrapper'
          });

          $(this).addClass('btn');

          $(this).wrap(newWrapper);
        });

        $('.form-actions .button', context).each(function () {
          var newWrapper = $('<button>', {
            'class': 'submit-wrapper disable border-0',
            'type': 'submit'
          });

          $(this).removeClass('btn-maroon');

          $(this).wrap(newWrapper);
        });
      }

      // Replacing remove button with icons.
      var removeButtonWrappers = $(once('removeIcons','.person-item', context));
      if (removeButtonWrappers.length) {
        $('.person-item .remove-button--wrapper', context).each(function ( index, element) {
          var locked = $(element).find('.remove-person-item').attr('disabled');
          var removeButton = $('<div>', {
            'class': 'remove-button--wrapper'
          });

          if(!locked) {
            removeButton.append($('<a>', {
              'class': 'remove-person-item js-form-submit form-submit',
              'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-remove-button',
              'type': 'submit',
              'id': 'edit-group-details-pane-person-details-' + index + '-remove-button',
              'name': 'remove_person_' + index,
              'value': ''
            }).append('<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M432 32H312l-9.4-18.7A24 24 0 0 0 281.1 0H166.8a23.72 23.72 0 0 0-21.4 13.3L136 32H16A16 16 0 0 0 0 48v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16zM53.2 467a48 48 0 0 0 47.9 45h245.8a48 48 0 0 0 47.9-45L416 128H32z"/></svg>'))
            ;
          }
          $(element).replaceWith(removeButton);
        });

        $('.person-item', context).each(function ( index, element) {
          $(this).find('label').addClass('js-form-required');
          $(this).find('input.form-control').prop('required',true);
        });
      }

      // Function to update the title of each person item.
      var updateTitles = function () {
        var items = wrapper.children('.person-item');
        items.each(function (index) {
          $(this).find('.person-item-title h3').text(index + 1);
        });
      };
      
      // Update items index.
      var updateIndexs = function () {
        wrapper.find('.person-item').each((index, el) => {
          el = $(el);
          let firstName = el.find('input[name*="[first_name]"]'); 
          let lastName = el.find('input[name*="[last_name]"]'); 
          let email = el.find('input[name*="[email]"]');
          firstName.attr('name', firstName.attr('name').replace(/\d+/, index));
          lastName.attr('name', lastName.attr('name').replace(/\d+/, index));
          email.attr('name', email.attr('name').replace(/\d+/, index));
        });
      };

      // Update the submit button when a person is removed.
      var updateSubmitButton = function () {
        var form = $('.uds-form');
        var submitButton = $('.form-actions .submit-wrapper .button');
        
        var inputs = form.find('.form-control[required]');
        var allFieldsFilled = true;

        inputs.each(function () {
          if ($(this).val() === '') {
            allFieldsFilled = false;
            return false; // exit the loop if any field is empty
          }
        });

        if (!allFieldsFilled) {
          submitButton.addClass('disabled');
          submitButton.parent().addClass('disabled');
        } else {
          submitButton.removeClass('disabled');
          submitButton.parent().removeClass('disabled');
        }
      };

      // Add click event for the add button.
      $(document).once().on('click', '.btn.add-person-item', function () {
        var index = wrapper.children('.person-item').length;

        var item = $('<div>', {
          'class': 'person-item d-flex js-form-wrapper form-wrapper',
          'data-drupal-selector': 'edit-group-details-pane-person-details-' + index,
          'id': 'edit-group-details-pane-person-details-' + index
        });

        var titleContainer = $('<div>', {
          'class': 'person-item-title sub-heading'
        }).append($('<h3>', {
          'text': index
        }));

        var firstNameField = $('<input>', {
          'type': 'text',
          'id': 'edit-group-details-pane-person-details-' + index + '-first-name',
          'name': 'group_details_pane[person_details][' + index + '][first_name]',
          'class': 'form-control',
          'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-first-name',
          'required': 'required'
        });
        var firstNameLabel = $('<label>', {
          'for': 'edit-group-details-pane-person-details-' + index + '-first-name',
          'class': 'js-form-required',
          'text': Drupal.t('First Name')
        });

        var lastNameField = $('<input>', {
          'type': 'text',
          'id': 'edit-group-details-pane-person-details-' + index + '-last-name',
          'name': 'group_details_pane[person_details][' + index + '][last_name]',
          'class': 'form-control',
          'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-last-name',
          'required': 'required'
        });
        var lastNameLabel = $('<label>', {
          'for': 'edit-group-details-pane-person-details-' + index + '-last-name',
          'class': 'js-form-required',
          'text': Drupal.t('Last Name')
        });

        var emailField = $('<input>', {
          'type': 'email',
          'id': 'edit-group-details-pane-person-details-' + index + '-email',
          'name': 'group_details_pane[person_details][' + index + '][email]',
          'class': 'form-email form-control',
          'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-email',
          'required': 'required'
        });
        var emailLabel = $('<label>', {
          'for': 'edit-group-details-pane-person-details-' + index + '-email',
          'class': 'js-form-required',
          'text': Drupal.t('Email')
        });

        item.append(titleContainer);

        item.append($('<div>', {
          'class': 'js-form-item form-item js-form-type-textfield form-item-group-details-pane-person-details-' + index + '-first-name js-form-item-group-details-pane-person-details-' + index + '-first-name form-group'
        }).append(firstNameLabel).append(firstNameField));

        item.append($('<div>', {
          'class': 'js-form-item form-item js-form-type-textfield form-item-group-details-pane-person-details-' + index + '-last-name js-form-item-group-details-pane-person-details-' + index + '-last-name form-group'
        }).append(lastNameLabel).append(lastNameField));

        item.append($('<div>', {
          'class': 'js-form-item form-item js-form-type-email form-item-group-details-pane-person-details-' + index + '-email js-form-item-group-details-pane-person-details-' + index + '-email form-group'
        }).append(emailLabel).append(emailField));

        var removeButton = $('<div>', {
          'class': 'remove-button--wrapper'
        }).append($('<a>', {
          'class': 'remove-person-item js-form-submit form-submit',
          'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-remove-button',
          'type': 'submit',
          'id': 'edit-group-details-pane-person-details-' + index + '-remove-button',
          'name': 'remove_person_' + index,
          'value': ''
        }).append('<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M432 32H312l-9.4-18.7A24 24 0 0 0 281.1 0H166.8a23.72 23.72 0 0 0-21.4 13.3L136 32H16A16 16 0 0 0 0 48v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16zM53.2 467a48 48 0 0 0 47.9 45h245.8a48 48 0 0 0 47.9-45L416 128H32z"/></svg>'))
        ;

        item.append(removeButton);
        item.hide().appendTo(wrapper).fadeIn();

        // Update titles of person items.
        updateTitles();
        addButton.appendTo(wrapper);
      });

      // Add click event for the remove button.
      wrapper.on('click', '.remove-person-item', function () {
        var items = wrapper.children('.person-item');
        if (items.length > 1) {
          $(this).closest('.person-item').fadeOut(function () {
            $(this).remove();
            updateTitles();
            updateIndexs();
            updateSubmitButton();
          });
        }
      });

      // Append the add button to the wrapper.
      if (!wrapper.has('.add-person-item').length) {
        addButton.appendTo(wrapper);
      }

      var examples = $(once('showExamples', '.upload-content', context));
      if (examples.length) {
        const uploadWrapper = document.querySelector('.upload-file-wrapper');
        var toggleinput = $('.upload-examples-toggle');
        var exampleContainer = $('.upload-examples');

        function isValidEmail(email) {
          email = email.trim()
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          return emailRegex.test(email);
        }
      
        function validateRows(data) {
          let errors = [];
      
          data.forEach((row, index) => {
              const [firstname, lastname, email] = row;
              let rowErrors = [];
      
              if (!firstname || !lastname || !email) {
                  rowErrors.push("Some values are missing");
              }
              if (!isValidEmail(email)) {
                  rowErrors.push("Invalid email format");
              }
      
              if (rowErrors.length > 0) {
                  errors.push({
                      rowIndex: index + 1,
                      errors: rowErrors
                  });
              }
          });
      
          return {
              ok: errors.length === 0,
              errors
          };
        }

        function removeHeaderRowIfPresent(data) {
          if (
            data.length > 0 &&
            typeof data[0][0] === "string" &&
            typeof data[0][1] === "string" &&
            typeof data[0][2] === "string"
          ) {
            const col1 = data[0][0].toLowerCase().trim();
            const col2 = data[0][1].toLowerCase().trim();
            const col3 = data[0][2].toLowerCase().trim();
        
            if (
              col1 === "first name" &&
              col2 === "last name" &&
              col3 === "email"
            ) {
              data.shift();
            }
          }
        
          return data;
        }
        
        const fillWithFile = function (row) {
          var index = wrapper.children('.person-item').length;

          var item = $('<div>', {
            'class': 'person-item d-flex js-form-wrapper form-wrapper',
            'data-drupal-selector': 'edit-group-details-pane-person-details-' + index,
            'id': 'edit-group-details-pane-person-details-' + index
          });

          var titleContainer = $('<div>', {
            'class': 'person-item-title sub-heading'
          }).append($('<h3>', {
            'text': index
          }));

          var firstNameField = $('<input>', {
            'type': 'text',
            'id': 'edit-group-details-pane-person-details-' + index + '-first-name',
            'name': 'group_details_pane[person_details][' + index + '][first_name]',
            'class': 'form-control',
            'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-first-name',
            'required': 'required',
            'value': row[0]
          });
          var firstNameLabel = $('<label>', {
            'for': 'edit-group-details-pane-person-details-' + index + '-first-name',
            'class': 'js-form-required',
            'text': Drupal.t('First Name')
          });

          var lastNameField = $('<input>', {
            'type': 'text',
            'id': 'edit-group-details-pane-person-details-' + index + '-last-name',
            'name': 'group_details_pane[person_details][' + index + '][last_name]',
            'class': 'form-control',
            'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-last-name',
            'required': 'required',
            'value': row[1]

          });
          var lastNameLabel = $('<label>', {
            'for': 'edit-group-details-pane-person-details-' + index + '-last-name',
            'class': 'js-form-required',
            'text': Drupal.t('Last Name')
          });

          var emailField = $('<input>', {
            'type': 'email',
            'id': 'edit-group-details-pane-person-details-' + index + '-email',
            'name': 'group_details_pane[person_details][' + index + '][email]',
            'class': 'form-email form-control',
            'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-email',
            'required': 'required',
            'value': row[2]
          });
          var emailLabel = $('<label>', {
            'for': 'edit-group-details-pane-person-details-' + index + '-email',
            'class': 'js-form-required',
            'text': Drupal.t('Email')
          });

          item.append(titleContainer);

          item.append($('<div>', {
            'class': 'js-form-item form-item js-form-type-textfield form-item-group-details-pane-person-details-' + index + '-first-name js-form-item-group-details-pane-person-details-' + index + '-first-name form-group'
          }).append(firstNameLabel).append(firstNameField));

          item.append($('<div>', {
            'class': 'js-form-item form-item js-form-type-textfield form-item-group-details-pane-person-details-' + index + '-last-name js-form-item-group-details-pane-person-details-' + index + '-last-name form-group'
          }).append(lastNameLabel).append(lastNameField));

          item.append($('<div>', {
            'class': 'js-form-item form-item js-form-type-email form-item-group-details-pane-person-details-' + index + '-email js-form-item-group-details-pane-person-details-' + index + '-email form-group'
          }).append(emailLabel).append(emailField));

          var removeButton = $('<div>', {
            'class': 'remove-button--wrapper'
          }).append($('<a>', {
            'class': 'remove-person-item js-form-submit form-submit',
            'data-drupal-selector': 'edit-group-details-pane-person-details-' + index + '-remove-button',
            'type': 'submit',
            'id': 'edit-group-details-pane-person-details-' + index + '-remove-button',
            'name': 'remove_person_' + index,
            'value': ''
          }).append('<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M432 32H312l-9.4-18.7A24 24 0 0 0 281.1 0H166.8a23.72 23.72 0 0 0-21.4 13.3L136 32H16A16 16 0 0 0 0 48v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16zM53.2 467a48 48 0 0 0 47.9 45h245.8a48 48 0 0 0 47.9-45L416 128H32z"/></svg>'));

          item.append(removeButton);
          wrapper.append(item);
        };
        
        if (uploadWrapper) {
          const observer = new MutationObserver((mutationsList) => {
              for (const mutation of mutationsList) {
                  if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                      mutation.addedNodes.forEach((node) => {
                          var filePath = $(node).find('a[type="text/csv"]').attr('href');          
                          if (filePath != undefined) {
                            const response = fetch(filePath)
                              .then(response => response.text())
                              .then(data => {
                                const parsedData = Papa.parse(data, { header: false, skipEmptyLines: true});
                                if (parsedData.data.length) {
                                  parsedData.data = removeHeaderRowIfPresent(parsedData.data); 
                                  let validation = validateRows(parsedData.data);
                                  if (validation.ok) {
                                    wrapper.remove(addButton);
                                    parsedData.data.forEach(row => {
                                      fillWithFile(row);
                                    })
                                    return true;
                                  }
                                }
                                return false;
                              })
                              .then(hasData => {
                                if (hasData) {
                                  wrapper.append(addButton);
                                  // Remove empty items.
                                  wrapper.find('.person-item').each((index, el) => {
                                    el = $(el);
                                    let firstName = el.find('input[name*="[first_name]"]').val(); 
                                    let lastName = el.find('input[name*="[last_name]"]').val(); 
                                    let email = el.find('input[name*="[email]"]').val();
                                    if (firstName.trim() === '' || lastName.trim() === '' || email.trim() === '') {
                                      el.remove();
                                    }
                                  });
                                  updateIndexs();
                                  updateTitles();
                                } else {
                                  messages.add('The csv file is incorrectly formatted, please use the template and follow the instructions.', {type: 'error'});
                                  window.scrollTo({top: 0, behavior: 'smooth'});
                                  setTimeout(() => {
                                    $('input[name="group_details_pane_upload_by_file_upload_file_remove_button"]').trigger('mousedown');
                                  }, 500);
                                }
                              })
                            .catch(err => console.log(err));
                          }
                      });
                  }
              }
          });
      
          observer.observe(uploadWrapper, {
              childList: true,
              subtree: false 
          });
        }

        $(toggleinput).on('click', function (e) {
          e.preventDefault();
          
          $(exampleContainer).toggleClass('opened');

          if($(exampleContainer).hasClass('opened')){
            $(exampleContainer).show('slow');
            $(this).text('HIDE EXAMPLES');
          } else{
            $(exampleContainer).hide('slow');
            $(this).text('SHOW EXAMPLES');
          }

          $(this).toggleClass('opened');
        });
      }
    }   
  }
})(jQuery, Drupal);
