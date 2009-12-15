function prime_multi_quote(link_obj)
{
	if (document.forms && document.forms['prime_multi_quote_form']) 
	{
		var quotes = '';
		for (var i=0; i<document.forms['prime_multi_quote_form'].quotes.length; i++) 
		{
			if (document.forms['prime_multi_quote_form'].quotes[i].checked) 
			{
				quotes += '&quotes[]=' + document.forms['prime_multi_quote_form'].quotes[i].value;
			}
		}
		link_obj.href += quotes;
	}
}