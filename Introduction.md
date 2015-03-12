#Basic information about using the product

# Yay! Another Facebook Bridge 3.2.5 #
_Tested to work with vBulletin 3.7.x and 3.8.x (3.6.x requires some manual edit, contact me for detail instruction)
Tested to work with vBulletin 4.0 (use different .xml import file)
**Please note**: this product requires PHP5. If you get weird error messages, please check if you PHP version is still 4 (which is very old and you should change to PHP5 as soon as possible). Also, MySQL 5 is strongly recommended_

## WHAT IS IT? ##
There are many vBulletin - Facebook bridge out there but installing is always the problem for most of forum owner. So I decided to build this product from scratch and provide you the ability to install this Facebook Bridge into your forum in 10 minutes. The detail install instruction can be found below (under the Feature section). A few technical notes: This product uses Facebook Connect to maintance connection between vBulletin forums and complicated (and awesome) Facebook servers. All most everything is automatically done by the script, even the Administrators don't need to do any manual editing (yeah, I'm talking about manual templates editing, you WON'T need to edit templates each time you implement a new style). Once again, most of everything is options which can be enabled/disabled via AdminCP or UserCP interface. All possible type of caching is use to improve performance and reduce heavy PHP load on server side or javascript load on client side. Well, it's quite a long paragraph for introducing. Let's make it work!

## SEE IT IN ACTION ##
Screencast:
  1. Installation: http://www.youtube.com/watch?v=ComjeipReE4
  1. Connecting: http://www.youtube.com/watch?v=i7m85O_iX70
  1. Posting and Notifications: http://www.youtube.com/watch?v=vBwwiffLvJ8

## MAIN FEATURE ##
  1. Damn easy to install!
  1. Loaded with long list of actions/notifications: posting thread, posting reply, rating thread, sending pm, sending visitor message, uploading image (avatar/profile picture), uploading pictures, comment on pictures. This list are designed to be expandable easily later by me or other people who loves to develope this product (well, if you do, contact me right now!)
  1. Ability to quick register with Facebook credential but Administrator can disable this function via AdminCP. Facebook proxied email addresses are handled completely and vBulletin can send email without any problems (of course, the member must grant the Email extend permission). There is a setting in AdminCP to disallow Facebook proxied email addresses also for boards that need email explicitly
  1. Ability to auto login with Facebook session (Administrator can turn this on or off in AdminCP and members can enable or disable this feature on their accounts either)
  1. Other abilities: Display a profile block on vBulletin member page. Display a wall tab/box on Facebook profile page. Synchronize avatar from Facebook.
  1. [AdminCP](AdminCP.md) Administrator can "edit" template bundle easily. Actually the old template will be deactivated, the new template will be registered and then all action associated with the old template with be updated with the new template bundle id. Oops, why you need to care about all of it? I did it for you already! Just go the the editing page and click Edit. Yay! All done
  1. [AdminCP](AdminCP.md) Staffs can manage connected users and see basic information about them (including granted permissions)
  1. [AdminCP](AdminCP.md) Administrator can decide to: migrate connected users from other products to YAFB, restrict private forums from being posted to Facebook, view detail log of YAFB in action, send notifications to connected users
  1. [Experimental](Experimental.md) I have added a fun feature that generate the Fans List, the photo will be posted to new thread with friend tagged (if available). The main purpose of this bonus feature is to demostrate the ability of the bridge. If you have an idea, contact me and I will implement it if I can
  1. [New](New.md) Friend Inviting functionality is added. Access from UserCP > Facebook Bridge > Invite Friends
  1. [New](New.md) Synchronize comments from Facebook back into vBulletin thread

## CHANGE LOG ##
  * Version 3.2.2 (released on Mar 20, 2010): All known issues have been fixed
  * Version 3.2 (released on Jan 22, 2010):
    * 3.2.1 Fix minor issues (file-based css, IE js, sharing with images, Connected User Manager improved, allow specific Facebook-based new register usergroup)
    * 3.2.2 Work smoothly with Facebook new policy about sending email (now we store REAL users' emails!)
    * 3.2.3 Allow synchronizing comments from Facebook into the associated thread in vBulletin

## NEW INSTALLATION ##
  1. Upload all files/folders in folder "upload" into your forum root (Please upload before importing product)
  1. Import Product:
    1. 1.1. Go to AdminCP
    1. 1.2. Plugins & Products ~> Manage Products ~> Add/Import Product:
      1. 1.2.1. Browse to the .xml file (use product-yafb.xml for vBulletin 3 and product-yafb\_vb4.xml for vBulletin 4)
      1. 1.2.2. Click Import

  1. Fill the settings:
    1. 2.1. Go to AdminCP
    1. 2.2. vBulletin Options ~> vBulletin Options ~> Yay! Another Facebook Bridge
    1. 2.2. Create a Facebook Application if you haven't had one for your forum. Refer to the Creating Facebook Application below if you don't know how to do it
    1. 2.3. Fill the API key and Secret into the approriate fields.
    1. 2.4. Click Save and you are done!

  1. Creating Facebook Application:
    1. Go to Facebook Developer > Create Application page http://www.facebook.com/developers/createapp.php (login if you didn't)
    1. Enter the Application Name. Suggestion: Put your forum name here. Don't forget to tick the Agree radiobox and then click Save Changes
    1. Now you can get your API Key and Secret (that are needed in step 2.3)
    1. And go to the Connect tab, fill your forum url into Connect URL, fill your domain into Base Domain.
    1. You are done with required information. But you can go through other tab and enter more detail about your forum, your application. I suggest putting a nice icon, banner into it. Happy customizing!

## FACEBOOK APPLICATION ADVANCED SETTINGS ##
  * If you want to use the Account Reclamation feature, put http://domain.com/forums/register.php?do=fbb_reclaim into Connect > Account Reclamation URL
  * To improve users experiences, put http://domain.com/forums/facebook.php?removed into Authentication > Post-Remove Callback URL

## UPGRADING ##
Just upload overwrite files/folders then reimport the xml file. Do not forget to check "Yes" under "Allow Overwrite" to upgrade

## CONFLICT ##
Since this product interfere with the registration procedure, I have found some other plugins/products that conficts with this product. Below is the full list of known confict and action taken to resolve the issue
  * No Spam (Human Verification): Temporary disabled during Facebook registration
  * vMail (Email Verification): Temporary disabled during Facebook registration

## OTHER PLUGINS/PRODUCTS ##
I have developed an easy to use options/permissions system that allow other plugins/products to add it's actions into this bridge and let the forum members experience a whole new era of connections. I did a small demostration with my kBank System (which is a money system with award/thank functionality and a useful hiding feature) and it runs pretty well. If you are interested in testing kBank in action with YAFB, contact me. If you are a developer who wants to integrate your plugins/products with this Bridge, contact me either! You are all welcome :D
