function SubmitFrom(elem)
{
    // Disable the button/input by first
    $(elem).prop("disabled", true);
    $(elem).closest("form").submit();
};
