$(document).ready(function () {
  if ($('.select2').length) {
    $('.select2').select2({
      width: '100%'
    }); 
    $('.select2-multiple').select2({
      theme: 'bootstrap4',
    });
  } 
  
  if ($('.summernote').length) {
    $('.summernote').summernote({
      height: 300 
    });
  }
});  

function isValidEmail(email) {
    let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function notif(message, type) {
    type = type ? type : "success";
    notyf.dismissAll();

    if (type == "success") {
        notyf.success(message);
        return;
    } else if (type == "error") {
        notyf.error({
          message: message,
          duration: 30000 
      });
      return;
    } else {
        notyf.warning(message);
        return;
    }
}

function loading() {
    $(".loader").removeClass('hidden');
}

function loadingComplete() {
    $(".loader").addClass('hidden');
}

function parseNumber(val) {
    if (!val) return 0;
    return parseFloat(val.toString().replace(/\./g, '').replace(',', '.')) || 0;
}

function formatNumber(val) {
    return new Intl.NumberFormat('id-ID').format(val);
}

$(document).on('blur', '.number-input', function () {
    $(this).val(formatNumber(parseNumber($(this).val())));
});

// Delete
$(document).on("click", ".delete-data", function (e) {
  e.preventDefault();

  let dataTable = $(this).attr('data-table');
  let id = $(this).attr('data-id');
  let url = $(this).attr('href');

  const activeTabHash = $('.nav-link.active').attr('href');

  Swal.fire({
    title: 'Are you sure?',
    text: "This action cannot be undone!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      loading();

      $.ajax({
        url: url,
        method: "DELETE",
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
          id: id
        },
        success: function () {
          notif('Data deleted successfully!');

          if (dataTable !== "none") {
            $('#' + dataTable).DataTable().ajax.reload();
            loadingComplete();
          } else {
            window.location.hash = activeTabHash;
            window.location.reload();
          }
        },
        error: function (xhr) {
          loadingComplete();

          let errorMessage = 'Something went wrong. Please check the form.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }

          notif(errorMessage, 'error');
        }
      });
    }
  });
});

// Reset database and seed
$(document).on('click', '.reset-data', function (e) {
  e.preventDefault();

  let url = $(this).attr('href');

  Swal.fire({
    title: 'Are you sure?',
    text: 'This will reset and seed the database.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, reset it!',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6'
  }).then((result) => {
    if (result.isConfirmed) {
      loading();
      window.location.href = url;
    }
  });
});

// Submit
$(document).on("submit", "#form", function(e) {
  e.preventDefault();

  let form = this;
  $(form).find('.number-input').each(function () {
    $(this).val(parseNumber($(this).val()));
  });
  let formData = new FormData(form);
  let label = $('#form button[type=submit]').html();
  let requireConfirmation = $('#form').attr('require-confirmation');

  function submitFormWithAjax() {
    $.ajax({
      url: form.action,
      method: 'POST',
      data: formData,
      processData: false,
      dataType: 'json',
      contentType: false,
      beforeSend: function () {
        loading();
        $('button[type=submit]').attr('disabled', true).text('Saving...');
      },
      success: function (response) {
        notif('Saved successfully!');
        let backUrl = $('#form').attr('back-url');

        if (response.redirect_url !== undefined) {
          setTimeout(() => {
            window.location.href = response.redirect_url;
          }, 500);
        } else if (response.data !== undefined && response.data.redirect_url !== undefined) {
          setTimeout(() => {
            window.location.href = response.data.redirect_url;
          }, 500);
        } else if (backUrl !== 'none') {
          setTimeout(() => {
            window.location.href = backUrl;
          }, 500);
        } else {
          setTimeout(() => {
            location.reload();
          }, 500);
        }
      },
      error: function (xhr) {
        let errorMessage = 'Something went wrong. Please check the form.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        notif(errorMessage, 'error');
        $('button[type=submit]').html(label).attr('disabled', false);
        loadingComplete();
      }
    });
  }

  // Handle confirmation logic using SweetAlert2
  if (requireConfirmation === 'true') {
    Swal.fire({
      title: 'Are you sure?',
      text: "Do you want to save this form?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, save it!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#aaa'
    }).then((result) => {
      if (result.isConfirmed) {
        submitFormWithAjax();
      }
    });
  } else {
    submitFormWithAjax();
  }
});

// Generic confirmation for regular forms
$(document).on('submit', 'form[require-confirmation]:not(#form)', function(e) {
  e.preventDefault();
  const form = this;

  Swal.fire({
    title: 'Are you sure?',
    text: 'This action will be submitted.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, continue!',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#aaa'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
});

