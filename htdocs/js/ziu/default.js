
var shbox = {
    init: function(){
        if (! location.search.match(/action=search/)) {
            $('.search').hide();
            shbox.shflag--;
        }
        this.listen();
    },
    shflag: 1,
    listen: function(){
        $('.shbox').click(function(){
            $('.search').toggle((shbox.shflag++ % 2) == 0);
        });
    }
};

// onload
$(function(){
    shbox.init();
});

