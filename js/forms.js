function SubmitFrom(elem)
{
    // Disable the button/input by first
    $(elem).prop("disabled", true);

    // Submit the form for non submit type inputs
    if ($(elem).attr('type') != 'submit')
        elem.form.submit();
};
