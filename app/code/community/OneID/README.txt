## Intro ##
Thanks for downloading the OneID Magento extension. We developed this against what was the latest Magento version at the time (1.7).
However, the extension will work with previous versions. Below are a few starter tips to get integrated with your custom theme.
As always, please reach out to support@oneid.com if you have any questions. We have a team of Magento experts who can help get OneID
into your custom Magento theme.


## Getting the OneID sign in button to show up ##
In using the 1.7 community edition, we extended the form.additional.info block of customer_account_login to show the OneID sign in button.
The form.additional.info doesn't appear to be present in previous versions. This is the block definition you should include : 

<block type="OneID/signin" name="oneid_signin_button" template="oneid/oneid/signin.phtml"></block>

You'll then want to render that block on the customer account login view (customer_account_login). 
This will likely include you modifying your

app/design/frontend/base/default/template/customer/form/login.phtml

to render the block.

## Getting the OneID Quick Fill buttons to show up ##

We built our templates based off of the 1.7 community edition. If you use this version
and use the Magento Onepage Checkout, you should be good to go. If you've made customizations
to your checkout, you can copy/paste the OneID blocks into your theme. The important files for Quickfill are:

app/design/frontend/base/default/template/oneid/checkout/onepage.pthml

OneID signin button on guest checkout
app/design/frontend/base/default/template/oneid/checkout/onepage/login.phtml

OneID Quickfill button on billing section
app/design/frontend/base/default/template/oneid/checkout/onepage/billing.phtml

OneID Quickfill button on shipping section
app/design/frontend/base/default/template/oneid/checkout/onepage/shipping.phtml

OneId Quickfill button on payment section
app/design/frontend/base/default/template/oneid/checkout/onepage/payment.phtml
