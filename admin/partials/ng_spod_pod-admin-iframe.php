<style>
    .iframe-wrapper { position: relative; overflow: hidden; width: 100%; padding-top: 56.25%; }
    .responsive-iframe { position: absolute; top: 0; left: 0; bottom: 0; right: 0; width: 100%; height: 100%; }
</style>
<div class="iframe-wrapper">
    <iframe
            frameborder="0"
            width="100%"
            height="auto"
            src="https://app.spod.com/fulfillment/woo-commerce/module?shopUrl=<?php echo $shopUrl; ?>&apiKey=&hmac=<?php echo $hmac; ?>"
            class="responsive-iframe">
    </iframe>
</div>
