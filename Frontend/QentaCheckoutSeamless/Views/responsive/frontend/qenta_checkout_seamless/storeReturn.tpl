<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/loose.dtd">
<html>
<!-- DS Store Return -->
    <head>
        <script type="text/javascript">
            function setResponse(response)
            {
                if(typeof parent.QentaCEE_Fallback_Request_Object == 'object')
                {
                    parent.QentaCEE_Fallback_Request_Object.setResponseText(response);
                }
                else
                {
                    console.log('Not a valid seamless fallback call.');
                }
            }
        </script>
    </head>

    <body onload='setResponse("{$qentaResponse}");'>

    </body>
</html>