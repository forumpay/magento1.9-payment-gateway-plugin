# Magento 1.9 Forumpay payment module <br> Installation guide

## Requirements

> Make sure you have the latest version of Magento 1 (1.9.3.6)

> You should already have downloaded .zip archive with Forumpay payment module.

## Installation

Transfer downloaded .zip archive to your server and unzip it.  
Now you need to merge **/app** magento directory with downloaded module.  

So unpacked **/app/code/community/Limitlex** goes into magento root **/app/code/community/**, and so  
**/app/etc/modules/Limitlex_ForumPay.xml** goes into magento's **/app/etc/modules/**.

It may be hard to merge it manually by copying files and folders, especially if you are not familiar with command line, so there is [Better way](https://unix.stackexchange.com/questions/149965/how-to-copy-merge-two-directories) using **rsync** tool.

Even better if you use FTP client, like _FileZilla_, so it shouldn't be big problem.

Otherwise, consider switching to **Magento 2**, where you just have to copy one folder.

## Configuration

Open magento admin panel and go to:  
**STORES** -> **Configuration** -> **Sales** -> **Payment Methods**

Scroll down until you find ForumPay dropdown. Open it.

Enable module by setting **Enabled** to '**Yes**'.

### What each field does:

1. **Title** The label of the payment method that is displayed when user is prompted to choose one.  
   You can leave default or set it to something like *Pay with crypto*.
2. **POS ID**  
   This is how payments coming to your wallets are going to be identified.  
   Must be an unique string. 
3. **API User / Merchant Id**  
   This is our identifier that we need to access the payment system.
   It can be found in your **Profile**.  
   [Go to profile >](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings)
4. **API Secret**  
   _Important:_ never share it to anyone!  
   Think of it as a password.
   Composed of two parts: first can be found in your profile.  
   And the second part was sent to your e-mail when account was created.  
   (If you can't find it, you can create new pair by **resseting Secret key** in your profile).
5. **New order status**  
   Which status order gets when user starts payment, and until transaction is performed.
6. **Order Status After Payment Captured**  
   Self-explanatory.
7. **Instructions**  
   Instructions that are going to be displayed for user during the process of placing order.
8. **Sort order**  
   Where the payment method must be placed inside the list of payment methods.  
   0 = First position, 1 = Second, 2 = Third ...
9.  **Payment Icon**
    Icon that is going to be displayed when we ask user to select payment method.

Don't forget to hit save button after fields are filled.

Magento may also ask you to refresh cache.  
So if you see something like: '*One or more of the Cache Types are invalidated*',  
just follow instructions.

## Webhook setup

**Webhook** allows us to check order status **independently** of user actions.

For example, if user **closes tab** after payment is started, we cannot determine what the status of order is.

Is it **Cancelled** or is it **Confirmed**?  
In our case it will be *Pending* **forever**.

### This is where webhook comes in.

If webhook is setted up in your [Profile](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings#webhook_notifications), it holds the url where it **calls back** on transaction status change.

Now, when user transfers his money and closes the tab before transaction is confirmed (~2 min), we loose state as before.  

But now after payment is confirmed, webhook tells our webshop to check order status. And then we get proper state of the order.

### How to set up webhook

Go to your [Profile](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings#webhook_notifications) and scroll down until you find Webhook URL.

Insert **URL** in this field:  
`YOUR_WEBSHOP/forumpay/index/webhook/`

Where **YOUR_WEBSHOP** is the URL of your webshop, so it should look like this:  
`https://my.webshop.com/forumpay/index/webhook/`

## Functionality

Now Forumpay payment method must be available during order checkout.

User has to select **ForumPay** and then choose the cryptocurrency.

When currency is selected, block with transaction data will be displayed.  
(Amount, Rate, Fee, Total, Expected time).

After placing order, user is is being redirected to the payment view.  
At this moment transaction is being created and it waits for money transfer.

Then user has 5 minutes to pay the order by scanning the **QR Code** or manually using address shown under the QR Code.

## Troubleshooting

> 'Forbidden' prints out when accessing webshop:  
This situation is probably caused by insufficient permissions inside your webserver filesystem.  
The quick fix is ~~**chmod 777 -R ./**~~ inside magento root.  
And must be never used in production, only for testing.  
[Proper solution >](https://devdocs.magento.com/guides/v2.4/config-guide/prod/prod_file-sys-perms.html)

> Can not select cryptocurrency, there is no dropdown.  
This issue happens probably because webshop's backend cannot access Payment Gateway.  
Please check if your API keys in configuration are correct.