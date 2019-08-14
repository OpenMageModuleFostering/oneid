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

to render the block as follows :
<?php echo $this->getChildHtml("oneid_signin_button"); ?>

## Getting the OneID Quick Fill buttons to show up ##

Similar to the OneID sign in button, we have written a block that will insert OneID QuickFill buttons onto the Magento OnePage checkout.
The block is defined as follows : 
<block type="core/template" name="oneid_onepage" template="oneid/checkout/oneid_onepage.phtml"></block>

We include this into the checkout_onepage_index handle in OneID.xml. If your buttons are not showing up, you may need to include this on your own.


## Getting a Create OneID button to show on order success ##
We've defined a block to show a Create OneID button, so that you can offer your customers a OneID after an order is placed.
In OneID.xml:

<reference name="checkout.success">
    <block type="core/template" name="oneid_provision" template="oneid/checkout/success/oneid_create.phtml"></block>
</reference>

You can print this block out in your theme's success.phtml with the line
<?php echo $this->getChildHtml("oneid_provision"); ?>


