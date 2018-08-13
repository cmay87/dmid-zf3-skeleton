Application = {
    lock: function(loadingText)
    {
        if (loadingText === undefined) {
            loadingText = 'Loading...';
        }
        
        $('#pageLockBackground').height($(document).height());
        $('#pageLockTextContent').text(loadingText);
        $('#pageLock').show();
    },
    unlock: function()
    {
        $('#pageLock').hide();
    }
};


// Add leading zeros to numbers
function padNumber(number, length)
{
    var str = number.toString();
    
    while (str.length < length) {
        str = '0' + str;
    }
    
    return str;
}