(function ($, Drupal, drupalSettings) {
  const messages = new Drupal.Message();

  Drupal.behaviors.groupAddAdmins = {
    attach: function (context, settings) {
      
      $(once('add-admin-btn', '#add-admin-btn',  context)).click(function(e){
          const newItemsContainer = $('.new-admins');
          let nextIndex = $('.admin-item').length + 1;

          newItemsContainer.append(`
          <div data-index="${nextIndex}" class="admin-item ${'admin-item-' + nextIndex } edit">
          <div>
            <div class="d-flex">
              <div class="admin-email">
                <label>${ nextIndex }.</label>
                <input type="email" name="email"/>
                <div class='text-danger error small'></div>
              </div>
              <div class="admin-primary">
                <input class="form-control gdmi-check" name="primary_admin" type="radio"/>
              </div>
              <div class="admin-invite-action action-edit" data-index="${nextIndex}">
                <span>CLOSE</span><i class="fas fa-sort-up ml-2"></i>
              </div>
              <div class="admin-invite-action action-remove enabled" data-index="${nextIndex}">
                <span>Remove</span><i class="fas fa-times ml-2"></i>
              </div>
            </div>
            <div class="admin-item-settings">
              <div class="message-container">
                <div class="message-container__content">
                  ${ getMessage(drupalSettings.tb_gdmi.messageCommunication) }
                </div>
                <button data-index="${nextIndex}" class="btn btn-primary text-white d-block ml-auto save-admin-btn" type="button">SAVE ADMIN</button>
              </div>
            </div>
          </div>
        </div>`);

        addBtn('show');
      });

      $(document).once('save-admin-btn').on('click', '.save-admin-btn', function () {

        let triggeredIndex = $(this).attr('data-index');
        const currentItem = $('.admin-item-' + triggeredIndex);
        const primaryAdmin = currentItem.find('input[name="primary_admin"]')
        const input = currentItem.find('.admin-email input');
        const error = currentItem.find('.admin-email .error');
        let groupId = $('#groupId').val();

        if (!isValidEmail(input.val())) {
          input.css({'border-color' : 'red'});
          error.text('Invalid email.');
          error.show();
          return;
        }

        if (isUsedEmail(input.val())) {
          input.css({'border-color' : 'red'});
          error.text('Email already in use.');
          error.show();
          return;
        }

        const isNew = currentItem.data('saved-admin') === undefined;

        $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());

        $.ajax({
          url: `/dashboard/groups/${groupId}/add-admin`,
          type: 'POST',
          data: {
            isNew,
            uid: isNew ? '' : currentItem.data('saved-admin'),
            email: input.val(),
            primary_admin: primaryAdmin.is(':checked') ? input.val() : ''
          },
          success: function (data) {
            if (data.status === 200) {

              if (data.response.message) {
                messages.add(data.response.message, {type: 'warning'});
              } else {
                userSuccessSaved(currentItem);
              }

              currentItem.data('saved-admin', data.response.uid);
              currentItem.find('.action-remove').data('saved-admin', data.response.uid);
              currentItem.find('input[name="primary_admin"]').val(data.response.uid);

              $('.ajax-progress-fullscreen').remove();

            }
          },
          error: function (jqXHR, textStatus) {
            messages.add('AJAX error: ' + textStatus, {type: 'error'});
          }
        });

      });

      $(document).once('admin-invite-action-remove').on('click', '.admin-invite-action.action-remove.enabled', function () {

        const element = $(this);
        const dataSavedAdmin = element.data('saved-admin')
        let triggeredIndex = element.attr('data-index');
        let groupId = $('#groupId').val();

        if (element.hasClass('enabled') && dataSavedAdmin !== undefined) {
          
          $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());

          $.ajax({
            url: `/dashboard/groups/${groupId}/remove-admin`,
            type: 'POST',
            data: {
              uid: dataSavedAdmin
            },
            success: function (data) {
              if (data.status === 200) {
                element.closest('.admin-item').remove();
                updateIndexsOrder(triggeredIndex);
              }
              
              if (data.status === 403) {
                messages.add(data.message, {type: 'warning'});
                window.scrollTo({
                  top: 0,
                  behavior: 'smooth',
                });
              }

              $('.ajax-progress-fullscreen').remove();
            },
            error: function (jqXHR, textStatus) {
              messages.add('AJAX error: ' + textStatus, {type: 'error'});
            }
          });

        } else {

          $('.admin-item-' + triggeredIndex).remove();
          addBtn('hide');
          updateIndexsOrder(triggeredIndex);

        }
      });

      $(document).once('admin-invite-action-edit').on('click', '.admin-invite-action.action-edit', function () {
        const element = $(this);
        let triggeredIndex = element.attr('data-index');

        if (element.hasClass('enabled')) {
          const editBtn = $('.admin-item-' + triggeredIndex + ' .action-edit');
          let btnTxt = editBtn.find('span').text();
          if (btnTxt == 'CLOSE') {
            const editBtn = $('.admin-item-' + triggeredIndex + ' .action-edit');
            editBtn.find('span').text('EDIT DETAILS');
            editBtn.find('svg').remove();
            editBtn.find('span').after('<i class="fas fa-sort-down ml-2"></i>');
            $('.admin-item-' + triggeredIndex + ' .admin-email').addClass('disabled');
            $('.admin-item-' + triggeredIndex + ' .admin-email input').prop("readonly", true);
            $('.admin-item-' + triggeredIndex).removeClass('edit');
            addBtn('hide');
            return;
          }
        }

        if (element.hasClass('enabled')) {
          const editBtn = $('.admin-item-' + triggeredIndex + ' .action-edit');
          editBtn.find('span').text('CLOSE');
          editBtn.find('svg').remove();
          editBtn.find('span').after('<i class="fas fa-sort-up ml-2"></i>');
          $('.admin-item-' + triggeredIndex + ' .admin-email').removeClass('disabled');
          $('.admin-item-' + triggeredIndex + ' .admin-email input').prop("readonly", false);
          $('.admin-item-' + triggeredIndex).addClass('edit');
          addBtn('show');
        }

      });
      
    },
  };

  function updateIndexsOrder() {
    const items = $('.admin-item');
    items.each((index, element) => {
      let item = $(element);
      let prevIndex = item.attr('data-index');
      item.removeClass('admin-item-' + prevIndex);
      item.addClass('admin-item-' + (index + 1));
      item.find('.admin-email label').text((index + 1) + '.');
      item.find('.action-edit').attr('data-index', (index + 1));
      item.find('.action-remove').attr('data-index', (index + 1));
      item.find('.save-admin-btn').attr('data-index', (index + 1));
    });
  }

  function isValidEmail(email) {
    var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
    return emailPattern.test(email);
  }

  function isUsedEmail(email) {
    let count = 0;
    const inputs = $('.admin-email input');
    inputs.each((index, element) => {
      element = $(element);
      if(element.val() === email) {
        count++;
      }
    });
    return count > 1;
  }

  function addBtn(action) {
    if (action == 'show') {
      $('#add-admin-btn').addClass('d-none');
      $('#btn-submit-form').addClass('btn-gray');
      $('#btn-submit-form').removeClass('btn-primary');
      $('#btn-submit-form').prop('type', 'button');
    } else {
      $('#add-admin-btn').removeClass('d-none');
      $('#btn-submit-form').removeClass('btn-gray');
      $('#btn-submit-form').addClass('btn-primary');
      $('#btn-submit-form').prop('type', 'submit');
    }
  }

  function  userSuccessSaved(currentItem) {

    const input = currentItem.find('.admin-email input');
    const error = currentItem.find('.admin-email .error');

    error.hide();
    input.css({'border-color' : '#CCC'});
    
    currentItem.find('.admin-email').addClass('disabled');
    currentItem.find('.action-edit').addClass('enabled');
    currentItem.removeClass('edit');
    input.prop("readonly", true);
    
    addBtn('hide');
    
    const editBtn = currentItem.find('.action-edit');
    editBtn.find('span').text('EDIT DETAILS');
    editBtn.find('svg').remove();
    editBtn.find('span').after('<i class="fas fa-sort-down ml-2"></i>');

  }

  function getMessage(settings) {
    return `
    <div class="message-heading">
      <span>Schedule:</span> ${settings?.schedule}
    </div>
    <div class="message-heading">
      <span>Message:</span> ${settings?.message}
    </div>
    <div class="message-heading">
      <span class="d-block">Title:</span>
      <span class="message-title">${settings?.title}</span>
    </div>
    <div>
      <div class="message-heading">
        <span>Body:</span>
      </div>
      <div class="message-body">
        ${settings?.body.value}
      </div>
    </div>
    `;
  }

  })(jQuery, Drupal, drupalSettings);