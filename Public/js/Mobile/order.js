//var menu = new Vue({
//      el: "#menu",
//      data: {
//          classify: [],
//          info: []
//      },
//      mounted: function() {
//          var that = this;
//          $.ajax({
//              url: '/index.php/Mobile/Index/ajaxGetFoodInfo',
//              contentType: false,
//              processData: false,
//              async: true,
//              cache: false,
//              dataType: 'json',
//              beforeSend: function() {
//                  layer.open({
//                      type: 2,
//                      shadeClose: false
//                  });
//              },
//              success: function(data) {
//                  console.log(data);
//                  that.classify = data.info;
//                  that.info = data.food_infos;
//                  that.$nextTick(function(){
//                      lazyload()
//                  });
//              },
//              complete:function() {
//                  layer.closeAll('loading');
//              },
//              error:function() {
//                  layer.open({
//                      content: '抱歉，出错了'
//                      ,skin: 'msg'
//                      ,time: 2 //2秒后自动关闭
//                    });
//              }
//          });
//          
//      },
//      methods:{
//          findfoodinfo(obj){
//              findfoodinfo(obj.currentTarget)
//          }
//      }
//  })

$(function (){
	layer.open({
		type: 2,
		shadeClose: false
	});
    var isPageHide = false;
    window.addEventListener('pageshow', function () {
        if (isPageHide) {
            layer.closeAll('loading');
            window.location.reload();
        }
    });
    window.addEventListener('pagehide', function () {
        isPageHide = true;
        layer.closeAll('loading');
    });
});
$('.dish-icon').width($('.dish-item').width() - 120);
$('.dish-icon').height(($('.dish-item').width() - 120) * 0.6);
var controller_url = '/index.php/Mobile/Index';

function ballFly(obj) {
    var flyElm = '<div class="flyElm"></div>';
    $('body').append(flyElm);
    var cart = $('.order-footer-icon');
    // $('.flyElm').animate({
    //     top: cart.offset().top + 6 + 'px',
    //     left: cart.offset().left + 6 + 'px',
    //     width: '15px',
    //     height: '15px'
    // },function(){        
    //     $(this).remove();
    // });
    $('.flyElm').fly({
        start: {
            left: obj.offset().left + 16, //开始位置（必填）.flyElm元素会被设置成position: fixed
            top: obj.offset().top + 16 //开始位置（必填）
        },
        end: {
            left: cart.offset().left + 6, //结束位置（必填）
            top: cart.offset().top + 6, //结束位置（必填）
            width: 15, //结束时高度
            height: 15 //结束时高度
        },
        speed: 1, //越大越快，默认1.2
        vertex_Rtop: 30, //运动轨迹最高点top值，默认20
        onEnd: function onEnd() {
            $('.flyElm').remove(); //移除dom
        } //结束回调
    });
}

function showCart() {
    var total = Number($("#Total").html()).toFixed(2);
    if (total == 0) {
        layer.open({
            content: '尚未添加菜品',
            skin: 'msg',
            time: 2 //2秒后自动关闭
        });
    } else {
        $('.order-cart').toggle();
    }
}
$('.order-cart').click(function (event) {
    $(this).hide();
});
$('.order-cart-content').click(function (event) {
    event.stopPropagation();
});
$(function () {
    var restaurantInfo = $("#restaurantInfo").data();
    var restaurant_id = restaurantInfo["restaurant_id"];
    var desk_code = restaurantInfo["desk_code"];
    sessionStorage.setItem("restaurant_id", restaurant_id);
    sessionStorage.setItem("desk_code", desk_code);
});

function foodMinus(obj) {
    var food_id = Number($(obj).data("food_id"));
    minus($('#foodlist .minus-btn[data-food_id="' + food_id + '"]')[0]);
}

function findfoodinfo(obj,flag) {
    var food_id = $(obj).data('food_id');
    var have_attribute = $(obj).data('have_attribute');

    var restaurant_id = $("#restaurantInfo").data('restaurant_id');
    var desk_code = $("#restaurantInfo").data('desk_code');
    $.ajax({
        type: "get",
        url: controller_url + "/findfoodinfo/food_id/" + food_id + "/restaurant_id/" + restaurant_id + '/desk_code/' + desk_code,
        beforeSend: function() {
        	if(flag==='1'){
        		layer.open({
	                type: 2,
	                shadeClose: false
	            });
        	}
        },
        success: function success(data) {
            $("#modelfood").html(data); //加载模态框
            if (have_attribute == '0') {
                ballFly($(obj));
                $("#food-checked").trigger('click');
                if ($(obj).siblings('.minus-btn').length == 0) {
                    var str = '<button class="minus-btn" onclick="foodMinus(this)" data-food_id="' + food_id + '">\
                                <i class="iconfont icon-minus"></i>\
                            </button>\
                            <span class="cart-num" data-food_id="' + food_id + '">1</span>';
                    $(obj).before(str);
                } else {
                    var num = Number($(obj).siblings('.cart-num').html()) + 1;
                    $(obj).siblings('.cart-num').html(num);
                }
            }
            if (have_attribute == '2') {
                $("#food-checked").data('have_attribute', '1');
            }
        },
        complete:function() {
            layer.closeAll('loading');
        }
    });
}

function showtypefood(i) {
    $.ajax({
        type: "get",
        url: controller_url + "/showtypefood/type/" + i + "",
        //dataType:"json",
        success: function success(data) {
            $("#food_info").html(data);
        }
    });
}

function PlaceOrder() {
    var total = Number($("#Total").html()).toFixed(2);
    if (total == 0) {
        layer.open({
            content: '尚没有添加菜品',
            skin: 'msg',
            time: 2 //2秒后自动关闭
        });
    } else {
        var list = {};
        $('#foodlist').children().each(function (k, v) {
            var temp = [];
            temp["0"] = $(this).data("food_id");
            temp["1"] = $(this).data("food_num");
            temp["2"] = $(this).data("attrs");
            list[k] = temp;
        });
        $.ajax({
            type: "post",
            url: controller_url + "/PlaceOrder/",
            data: list,
            dataType: 'json',
            beforeSend: function beforeSend() {
                layer.open({
                    type: 2,
                    shadeClose: false
                });
            },
            success: function success(data) {
                if (data.code == 1) {
                    var order_sn = data.data['order_sn'];
                    var Total = $("#Total").html();
                    window.location.href = controller_url + "/selectEatTime/order_sn/" + order_sn;
                } else if (data.code == 2) {
                    var order_sn = data.data['order_sn'];
                    window.location.href = controller_url + "/pay_old_user/order_sn/" + order_sn;
                }else if(data.code == 3){
                    var order_sn = data.data['order_sn'];
                    var desk_code = data.desk_code;
                    window.location.href = controller_url + "/pay_old/order_sn/" + order_sn + "/desk_code/" + desk_code;
                }else {
                    alert(data.msg);
                }
            },
            error: function error(data) {
                layer.closeAll('loading');
                alert("there is a error!");
            }
        });
    }
}