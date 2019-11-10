require(['jquery', 'jquery/ui'], function(jQuery){ 
    jQuery(document).ready(function() {
        jQuery('.admin__field.field.field-product_id ').css("display", "none");
        jQuery('.admin__field.field.field-category_id  ').css("display", "none");
        $typeValue = jQuery('#Smyappslider_url_type').val();
        if($typeValue == "Product")
        {
            jQuery('.admin__field.field.field-product_id ').css("display", "block");
            jQuery('.admin__field.field.field-category_id  ').css("display", "none");
        }
        else{
            jQuery('.admin__field.field.field-product_id ').css("display", "none");
            jQuery('.admin__field.field.field-category_id  ').css("display", "block");
        }
        
        jQuery('#Smyappslider_url_type').change( function() {
        if(this.value == "Product")
        {
           
            jQuery('.admin__field.field.field-product_id ').css("display", "block");
            jQuery('.admin__field.field.field-category_id  ').css("display", "none");
        }
        else{
           
            jQuery('.admin__field.field.field-product_id ').css("display", "none");
            jQuery('.admin__field.field.field-category_id  ').css("display", "block");
        }
      });
    });
});