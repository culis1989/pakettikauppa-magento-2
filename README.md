# Pakettikauppa (M2) shipping module
### Install instructions:
1. Check that Magento can write to pub/labels directory
2. Copy files into your magento installation
3. Activate module (but module should be activated by default)
4. Insert your API credentials: Stores -> Configurations -> Pakettikauppa

### How to use:
1. Add item to cart and go to checkout
2. Fill in billing and shipping details
3. Choose from one of shipping methods that are based on your shipping zip code
4. If you want to use another pickup point zip insert new zip into field and click search
5. Once pickup point is selected finnish the order
6. Now login to Magento (admin) and see your order. Beside shipping address you will see Pickup Point details.
7. Once shipment is sent tracking will be added automaticly
