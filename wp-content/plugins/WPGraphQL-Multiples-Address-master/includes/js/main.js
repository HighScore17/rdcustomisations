function multiple_address_add(e)
{
    e.preventDefault();
    const formData = new FormData(document.getElementById('form_add'));
    formData.append("action", "multiple_address_add");
    for(let pair of formData.entries())
    {
        console.log(pair[0], ": ", pair[1]);
    }
    fetch(ajaxurl, {
        method: "post",
        body: formData
    }).then(response => response.json())
    .then(response => {
        console.log(response);
    })
    .catch(err => {
        console.error(err);
    })
    return false;
}