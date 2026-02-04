<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="background-color: rgba(9, 30, 66, 0.06); font-size: 14px; color: #1c2127; font-family: -apple-system, 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Open Sans', 'Helvetica Neue', sans-serif; margin: 0;">
<table border="0" cellspacing="0" cellpadding="0" width="100%">
  
  <!-- Header -->
  <tr>
    <td style="text-align: center; border-top: 2px solid #fbca3e; width: 100%;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td valign="middle">
                                <img src="https://app.cywise.io/cywise/img/cywise-35x35.png" height="33" style="height: 33px; margin: 20px; display: block;">
                            </td>
                            <td valign="middle">
                                <b style="font-size: 18px;">Cywise</b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td align="center" style="padding: 20px; padding-top: 0;">
      <table border="0" cellspacing="0" cellpadding="0" style="border: 1px solid rgba(0, 0, 0, 0.175); background-color: white; max-width: 600px; border-radius: 5px;">
        <tr>
          <td style="padding: 20px; font-size: 16px; line-height: 1.6;">
            @yield('content')
          </td>
        </tr>
      </table>
    </td>
  </tr>
  
  <!-- Footer -->
  <tr>
    <td style="padding-bottom: 20px; text-align: center; font-size: 12px;">
        </span> Cywise <span style="color: #fbca3e">|</span> 178 boulevard Haussmann, 75008 Paris, France
    </td>
  </tr>
</table>
</body>
</html>
