<?php

auth_require(true);

$SIDEBAR_DATA='
This is the PEAR administration page.
<noscript><p>
<!-- be annoying! -->
<b><blink>You must enable Javascript to use this page!</blink></b>
</p></noscript>
';

response_header("PEAR Administration");

//print "GET: ";var_dump($HTTP_GET_VARS);print "<br />\n";
//print "POST: ";var_dump($HTTP_POST_VARS);print "<br />\n";

// {{{ adding and deleting notes

if (@$cmd == "Add note" && !empty($note) && !empty($key) && !empty($id)) {
    add_note($key, $id, $note);
    unset($cmd);
}

elseif (@$cmd == "Delete note" && !empty($id)) {
    delete_note($id);
}

// }}}

// {{{ open account

elseif (@$cmd == "Open Account" && !empty($uid)) {
    // another hack to remove the temporary "purpose" field
    // from the user's "userinfo"
    if (open_account($uid)) {
	print "<p>Opened account $uid...</p>\n";
    }
}

// }}}
// {{{ reject account request

elseif (@$cmd == "Reject Request" && !empty($uid)) {
    if (reject_account_request($uid, $reason)) {
	print "<p>Rejected account request for $uid...</p>\n";
    }
}

// }}}
// {{{ delete account request

elseif (@$cmd == "Delete Request" && !empty($uid)) {
    if (delete_account($uid)) {
	print "<p>Deleted account request for \"$uid\"...</p>";
    }
}

// }}}

// {{{ javascript functions

?>
<script language="javascript">
<!--

function confirmed_goto(url, message) {
    if (confirm(message)) {
        location = url;
    }
}

function confirmed_submit(button, action, required, errormsg) {
    if (required && required.value == '') {
	alert(errormsg);
	return;
    }
    if (confirm('Are you sure you want to ' + action + '?')) {
	button.form.cmd.value = button.value;
	button.form.submit();
    }
}

// -->
</script>
<?php

// }}}

do {

    // {{{ "approve account request" form

    if (!empty($acreq)) {
	$requser =& new PEAR_User($dbh, $acreq);
	if (empty($requser->name)) {
	    break;
	}
        list($purpose, $moreinfo) = @unserialize($requser->userinfo);

	border_box_start("Account request from $requser->name &lt;$requser->email&gt;");
?>      <table cellpadding="2" cellspacing="0" border="0">
       <tr>
        <td>Requested User Name:</td>
        <td><?= $requser->handle ?></td>
       </tr>
       <tr>
        <td>Real Name:</td>
        <td><?= $requser->name ?></td>
       </tr>
       <tr>
        <td>Email Address:</td>
        <td><a href="mailto:<?= $requser->email ?>"><?= $requser->email ?></td>
       </tr>
       <tr>
        <td>MD5-encrypted password:</td>
        <td><?= $requser->password ?></td>
       </tr>
       <tr>
        <td>Purpose of account:</td>
        <td valign="top"><?= $purpose ?></td>
       </tr>
       <tr>
        <td>More information:</td>
        <td><?= $moreinfo ?></td>
       </tr>
      </table>
<?php

	border_box_end();
	print "<br />\n";
	border_box_start("Notes for user $requser->handle");
	$notes = $dbh->getAssoc("SELECT id,nby,ntime,note FROM notes ".
				"WHERE uid = ? ORDER BY ntime", true,
				array($requser->handle));
	$i = "      ";
	if (is_array($notes) && sizeof($notes) > 0) {
	    print "$i<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\n";
	    foreach ($notes as $nid => $data) {
		list($nby, $ntime, $note) = $data;
		print "$i <tr>\n";
		print "$i  <td>\n";
		print "$i   <b>$nby $ntime:</b>";
		if ($nby == $PHP_AUTH_USER) {
		    $url = "$PHP_SELF?acreq=$acreq&cmd=Delete+note&id=$nid";
		    $msg = "Are you sure you want to delete this note?";
		    print "[<a href=\"javascript:confirmed_goto('$url', '$msg')\">delete your note</a>]";
		}
		print "<br />\n";
		print "$i   ".htmlspecialchars($note)."\n";
		print "$i  </td>\n";
		print "$i </tr>\n";
		print "$i <tr><td>&nbsp;</td></tr>\n";
	    }
	    print "$i</table>\n";
	} else {
	    print "No notes.";
	}
	print "$i<form action=\"$PHP_SELF\" method=\"POST\">\n";
	print "$i<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\n";
	print "$i <tr>\n";
	print "$i  <td>\n";
	print "$i   To add a note, enter it here:<br />\n";
	print "$i    <textarea rows=\"3\" cols=\"55\" name=\"note\"></textarea><br />\n";
	print "$i   <input type=\"submit\" value=\"Add note\" name=\"cmd\">\n";
	print "$i   <input type=\"hidden\" name=\"key\" value=\"uid\">\n";
	print "$i   <input type=\"hidden\" name=\"id\" value=\"$requser->handle\">\n";
	print "$i   <input type=\"hidden\" name=\"acreq\" value=\"$acreq\">\n";
	print "$i  </td>\n";
	print "$i </tr>\n";
	print "$i</table>\n";
	print "$i</form>\n";

	border_box_end();
?>

<form action="<?= $PHP_SELF ?>" method="POST">
<input type="hidden" name="cmd" value="">
<input type="hidden" name="uid" value="<?= $requser->handle ?>">
<table cellpadding="3" cellspacing="0" border="0" width="90%">
 <tr>
  <td align="center"><input type="button" value="Open Account" onclick="confirmed_submit(this, 'open this account')" /></td>
  <td align="center"><input type="button" value="Reject Request" onclick="confirmed_submit(this, 'reject this request', this.form.reason, 'You must give a reason for rejecting the request.')" /></td>
  <td align="center"><input type="button" value="Delete Request" onclick="confirmed_submit(this, 'delete this request')" /></td>
 </tr>
 <tr>
  <td colspan="3">
   If dismissing an account request, enter the reason here
   (will be emailed to <?= $requser->email ?>):<br />
   <textarea rows="3" cols="60" name="reason"></textarea>
  </td>
</table>
</form>

<?php
    // }}}
    // {{{ admin menu
    } else {

	border_box_start("Account Requests", "30%");
	$requests = $dbh->getAssoc("SELECT handle,name,email FROM users ".
				   "WHERE registered = 0");
	if (is_array($requests) && sizeof($requests) > 0) {
	    foreach ($requests as $handle => $data) {
		list($name, $email) = $data;
		print "<a href=\"$PHP_SELF?acreq=$handle\">$name ($handle)</a><br />\n";
	    }
	} else {
	    print "No account requests.";
	}
	border_box_end();
    }

    // }}}

} while (false);

response_footer();

?>