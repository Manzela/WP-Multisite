<!DOCTYPE html>
<html dir="rtl" lang="{{ get_bloginfo('language') }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{!! get_bloginfo('name') !!}</title>
</head>
<body class="{{ implode(' ', get_body_class()) }}" dir="rtl">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container">
        <tr>
            <td align="center" valign="top">
                <!-- Header -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
                    <tr>
                        <td valign="middle" style="display: flex; justify-content: start; align-items: center; padding: 20px;">
                            @if($img = get_site_icon_url())
                                <img src="{{ $img }}" alt="{{ get_bloginfo('name') }}" style="left: 0; max-width: 50px;">
                            @endif
                            <h1 style="margin-right: 10px;">{{ $email_heading }}</h1>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html> 