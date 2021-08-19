$(document).ready(function() {
    showtime2();
    var status_id = sessionStorage.getItem('status_id');
    if(status_id) {
    	showinfoStatus(status_id);
    }
});

function  showAllCategory() {
    sessionStorage.setItem('status_id','');
    location.reload();
}

function showtime() {
    document.getElementById('show1').style.display = "";
}

function hiddentime() {
    document.getElementById('show1').style.display = "none";
}

function showtime2() {
    var hschek = $("input[name='is_timing']").is(':checked');
    if (hschek) {

        document.getElementById('show2').style.display = "";
    } else {
        document.getElementById('show2').style.display = "none";
    }

}

//新增菜品分类
function show_addSort() {
    $("#addSort").modal('show');
    $('#way').val(0);
    $('#category_name').val('');
    $('#category_english_name').val('');
    $("input[name='is_timing']").prop("checked", false);
}
//编辑菜品分类
function modify1(c) {
    $('#way').val(1);
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/updDishestype",
        data: { "category_id": c },
        success: function(data) {
            $('#restaurant_id').attr("value", data.restaurant_id);
            $('#category_id').attr("value", data.category_id);
            $('#category_name').val(data.category_name);
            $('#category_english_name').val(data.category_english_name);
            if (data.img) {
                $("#classify-icon").attr('src', data.img);
                $("#img_url").val(data.img);
            } else {
                $("#classify-icon").attr('src', '/Public/Uploads/ICO/2017-09-05/59ad812a73b93.png');
                $("#img_url").val('/Public/Uploads/ICO/2017-09-05/59ad812a73b93.png');
            }

            if (data.is_time == 1) {
                $("input[name='is_timing']").prop("checked", true);
                $("#show2").show();
                $("#time").html("");
                if (data.category_time) {
                    $.each(data.category_time, function(k, v) {
                        var time1 = v['day_start_time'];
                        var time2 = v['day_end_time'];
                        console.log(v);
                        var str = '<div class="modal-item">\
                    					<div class="inline-block">\
	                        				<label for="startTime">'+vm.langData.start[vm.lang]+':</label>\
	                        				<input type="text" class="startTime selectIcon" id="startTime" name="startTime" value="' + time1 + '">\
                        				</div>\
                        				<label for="endTime">'+vm.langData.end[vm.lang]+':</label>\
                        				<input type="text" name="endTime selectIcon" class="endTime" id="endTime" value="' + time2 + '">\
                        				<button class="remove">\
                        					<img src="/Public/images/remove_circle.png">\
                        				</button>\
                        			</div>';
                        $("#time").append(str);
                    });
                    triggerTime();
                }

                $("#day").html("");
                if (data.category_timing) {
                    $.each(data.category_timing, function(k,v) {
                    	
                        var dayStartTime = v['start_time'];
                        var dayEndTime = v['end_time'];
                        var str = '<div class="modal-item">\
										<input type="checkbox" name="monday" value="1"><label>'+vm.langData.Monday[vm.lang]+'</label>\
										<input type="checkbox" name="tuesday" value="2"><label>'+vm.langData.Tuesday[vm.lang]+'</label>\
										<input type="checkbox" name="wednesday" value="3"><label>'+vm.langData.Wednesday[vm.lang]+'</label>\
										<input type="checkbox" name="thursday" value="4"><label>'+vm.langData.Thursday[vm.lang]+'</label>\
										<input type="checkbox" name="friday" value="5"><label>'+vm.langData.Friday[vm.lang]+'</label>\
										<input type="checkbox" name="saturday" value="6"><label>'+vm.langData.Saturday[vm.lang]+'</label>\
										<input type="checkbox" name="sunday" value="0"><label>'+vm.langData.Sunday[vm.lang]+'</label>\
										<div class="inline-block" style="width:100px; margin-right:0">\
				        					<input type="text" class="dayStartTime selectIcon" id="dayStartTime" name="dayStartTime" style="width:100px" value="' + dayStartTime + '" autocomplete="off">\
				        				</div> - \
										<div class="inline-block" style="width:100px; margin-right:0">\
				        					<input type="text" class="dayEndTime selectIcon" id="dayEndTime" name="dayEndTime" style="width:100px" value="' + dayEndTime + '" autocomplete="off">\
				        				</div>\
										<button class="remove">\
											<img src="/Public/images/remove_circle.png">\
										</button>\
		                            </div>';
                        $("#day").append(str);
                        $('.remove').click(function(){
							$(this).parent().remove();
						});
                    });

                    $.each(data.category_timing, function(k1, v1) {
                        var timingDay = v1['timing_day'];
                        var dayStartTime = v1['start_time'];
                        var dayEndTime = v1['end_time'];
                       	
                       	$.each($("#day div.modal-item").eq(k1).find("input[type=checkbox]"), function(index,item) {
                       		$.each(timingDay, function(k2, v2) {
                       			if($(item).val() == v2){
                       				$(item)[0].checked = true;
                       			}
                       		});
                       	});	

                        $("#day select").each(function(k5, v5) {
                            if (k5 == k1 * 2) {
                                $(this).children().each(function() {
                                    if ($(this).html() == dayStartTime) {
                                        $(this)[0].selected = true;
                                    };
                                });
                            } else if ((k5 == k1 * 2 + 1)) {
                                $(this).children().each(function() {
                                    if ($(this).html() == dayEndTime) {
                                        $(this)[0].selected = true;
                                    };
                                });
                            }
                        });
                    });
                    triggerTime();
                }
            } else {
                $("input[name='is_timing']").prop("checked", false);
            }
        },
        error: function() {
            alert(vm.langData.error[vm.lang]);
        }
    });
}

function deltype(cid) {
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "get",
            url: "/index.php/admin/dishes/delDishestype/category_id/" + cid + "",
            success: function(data) {
                if (data && data.code == 0) {
					layer.msg(vm.langData.canNotDeleteCategory[vm.lang]);
                } else {
					$('#mytype').html(data);
					layer.msg(vm.langData.success[vm.lang]);
                }
            },
            error: function() {
                alert(vm.langData.error[vm.lang]);
            }
        });
    });
}

//改变菜品分类上下架操作
function changeCategorystatu(i) {
    var theEvent = window.event || arguments.callee.caller.arguments[0];
    theEvent.preventDefault();
    layer.confirm('', {
        title: '提示',
        content: '确定要更改菜品分类状态吗?',
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "get",
            url: "/index.php/admin/Dishes/updDishestypeState/category_id/" + i,
            async: true,
            success: function(data) {
                layer.msg('更改成功');
                $('#mytype').html(data);
                //alert("编辑成功！");
                //$("input[type='reset']").trigger("click");
                $("#classify-icon").attr('src', '/public/images/defaultFoodCate.png');

                var file = $("#user_define_img")
                file.after(file.clone().val(""));
                file.remove();
            }
        });
    });
}

//改变菜品上下架操作
function changestatu(i) {
	var theEvent = window.event || arguments.callee.caller.arguments[0];
	theEvent.preventDefault();
    var category_id = $("#category_id").val();
    if (category_id == "") {
        category_id = 0;
    } else {
        category_id = category_id;
    }
    var page = parseInt($('.current').data('page'));
    if (page == "NaN") {
        page == 1;
    } else {
        page = parseInt($('.current').data('page'));
    }
    layer.confirm('', {
        title: vm.langData.changeDishStatus[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "get",
            url: "/index.php/admin/Dishes/updstate/food_id/" + i + "/category_id/" + category_id + "/page/" + page + "",
            async: true,
            success: function(data) {
                if(data=='null'){
                    layer.msg("上架失败，该套餐单选必选菜品已全下架");
                    location.reload();
                }else{
                    layer.msg(vm.langData.success[vm.lang]);
                   if(sessionStorage.getItem('status_id') !=""){location.reload();}else {$('#mytr').html(data);}
                }
            }
        });
    });
}

//删除菜品表信息
function delfoodinfo(food_id) {
    var category_id = $("input[name='category_name']").val();
    var tr_leng = $("#mytr").children().children('tr').length;
    if($('.current').text() != undefined){
		if (tr_leng > 2) {
		    var page = parseInt($('.current').text());
		} else {
		    var page = parseInt($('.current').text() - 1);
		}
	}else{
		var page = 1;
	}
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
    	$.ajax({
    	    type: "get",
    	    url: "/index.php/admin/Dishes/delfoodinfo/food_id/" + food_id + "/page/" + page + "",
    	    async: true,
    	    success: function(data) {
    	        //$('#mytr').html(data);
    	        layer.msg(vm.langData.success[vm.lang]);
    	        setTimeout(function () {
                    location.reload();
                },3);
    	    }
    	});
    });
}

//删除菜品关联表信息
function delfoodinfo1(id) {
    var category_id = $("#category_id").val();
    var tr_leng = $("#mytr").children().children('tr').length;
    console.log(tr_leng);
    if (tr_leng > 2) {
        var page = parseInt($('.current').text());
    } else {
        var page = parseInt($('.current').text() - 1);
    }
    console.log(category_id);
    console.log(page);
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
    	$.ajax({
    	    type: "get",
    	    url: "/index.php/admin/Dishes/delfoodinfo1/id/" + id + "/category_id/" + category_id + "/p/" + page + "",
    	    async: true,
    	    success: function(data) {
    	        $('#mytr').html(data);
    	        layer.msg(vm.langData.success[vm.lang]);
    	    }
    	});
    });
}
//菜品分类数据上移
function moveup1(obj) {
    var sort = $(obj).data('sort');
    var category_id = $(obj).data('category_id');
    var tr = $(obj).parents("tr");

    /*console.log(tr.index());*/
    if (tr.index() != 0) {
        var up_sort = $(obj).parent().parent().prev().find(".rank-up").data('sort');
        $.ajax({
            type: "post",
            url: "/index.php/admin/dishes/moveup1",
            data: { "sort": sort, "up_sort": up_sort, "category_id": category_id },
            success: function(data) {
                $('#mytype').html(data);
                layer.msg(vm.langData.success[vm.lang]);
            },
            error: function() {
                alert(vm.langData.error[vm.lang]);
            }
        });
    }
}

//菜品分类数据下移
function movedown1(obj) {
    var len = parseInt(($("#mytype").find('tr').length) - 1);
    var sort = $(obj).data('sort');
    var category_id = $(obj).data('category_id');
    var tr = $(obj).parents("tr");
    if (tr.index() != len) {
        var down_sort = $(obj).parent().parent().next().find(".rank-up").data('sort');
        $.ajax({
            type: "post",
            url: "/index.php/admin/dishes/movedown1",
            data: { "sort": sort, "down_sort": down_sort, "category_id": category_id },
            success: function(data) {
                $('#mytype').html(data);
                layer.msg(vm.langData.success[vm.lang]);
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }
}

/*
//菜品记录表排序，但排序ID是用的(food表),上移数据
function moveup2(obj){
	var when_sort = $(obj).data('sort');
	var when_food_id = $(obj).data('food_id');
	var page = $(".current").data('page');	//当前页数
	var category_id = $('.dishes-classify-table tr.active').data('category_id');        //分类名称
	if(page == undefined){
		page = 1;
	}
	$.ajax({
		type:"get",
		url:"/index.php/admin/dishes/moveup2/category_id/"+category_id+"/when_sort/"+when_sort+"/when_food_id/"+when_food_id,
		async:true,
		dataType:"json",
		success:function(data){
			if(data.code == 1){
				$.ajax({
					type:"get",
					url:"/index.php/admin/dishes/showDisinfoBykey/category_id/"+category_id,
					success:function(data){
						$("#mytr").html(data);
					},
					error:function(){
						layer.msg(vm.langData.error[vm.lang]);
					}
				});	
			}else if(data.code == 2){
				layer.msg(vm.langData.lastChild[vm.lang]);
			}
		}
	});
}

//菜品记录表排序，但排序ID是用的(food表),下移数据
function movedown2(obj){
	var when_sort = $(obj).data('sort');
	var when_food_id = $(obj).data('food_id');
	var page = $(".current").data('page');	//当前页数
	var category_id = $('.dishes-classify-table tr.active').data('category_id');        //分类名称
	if(page == undefined){
		page = 1;
	}
	$.ajax({
		type:"get",
		url:"/index.php/admin/dishes/movedown2/category_id/"+category_id+"/when_sort/"+when_sort+"/when_food_id/"+when_food_id,
		async:true,
		dataType:"json",
		success:function(data){
			console.log("movedown:"+data.code);
			if(data.code == 1){
				$.ajax({
					type:"get",
					url:"/index.php/admin/dishes/showDisinfoBykey/category_id/"+category_id,
					success:function(data){
						$("#mytr").html(data);
					},
					error:function(){
						layer.msg(vm.langData.error[vm.lang]);
					}
				});	
			}else if(data.code == 2){
				layer.msg(vm.langData.lastChild[vm.lang]);
			}
		}
	});
}
*/


//菜品记录表排序，但排序ID是用的(food表),上移数据
function moveup2(obj){
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var page = $(".current").data('page');	//当前页数
    var food_category_id = $(obj).data('food_category_id');        //分类名称
    var when_tr = parseInt($(obj).data('index'));
    if(page == undefined){
        page = 1;
    }

    if (page == 1 && when_tr == 1) {
        return false;
    }
    // 上个菜品的id和排序
    var up_sort = $(obj).parent().parent().prev().find(".rank-up").data('sort');
    var up_food_id = $(obj).parent().parent().prev().find(".rank-up").data('food_id');

    $.ajax({
        type:"POST",
        url:"/index.php/admin/dishes/upCategoryFoodSort",
        data:{"food_id":food_id,"sort":sort,"up_food_id":up_food_id,"up_sort":up_sort},
        async:true,
        dataType:"json",
        success:function(data){
            if(data.code == 1){
                $.ajax({
                    type:"get",
                    url:"/index.php/admin/dishes/showDisinfoBykey/category_id/"+food_category_id,
                    success:function(data){
                        $("#mytr").html(data);
                    },
                    error:function(){
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                });
            }else if(data.code == 2){
                layer.msg(vm.langData.lastChild[vm.lang]);
            }
        }
    });
}

//菜品记录表排序，但排序ID是用的(food表),下移数据
function movedown2(obj){
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var page = parseInt($(".current").html());
    var food_category_id = $(obj).data('food_category_id');        //分类名称
    var pageArr = new Array();
    if(page == undefined){
        page = 1;
    }

    $(".pagination").children().children('a').each(function(index, element) {
        var when_page = parseInt($(element).data('page'));
        pageArr[index] = when_page;
    });
    var max = pageArr[0]
    for (var i = 1; i < pageArr.length; i++) {
        if (pageArr[i] > max) {
            max = pageArr[i]; //获取最大页数
        }
    }
    var last_tr = $("#mytr").children().children('tr:last'); //获取最后一个tr
    var downObj = $(obj).parent(); //获取点击时的tr
    if (page == max && last_tr == downObj) { //如果当前页是最后一页且所点击的tr是最后一个tr，则中止操作
        return false;
    }

    var down_sort = $(obj).parent().parent().next().find(".rank-up").data('sort');
    var down_food_id = $(obj).parent().parent().next().find(".rank-up").data('food_id');

    $.ajax({
        type:"POST",
        url:"/index.php/admin/dishes/downCategoryFoodSort",
        data:{"food_id":food_id,"sort":sort,"down_food_id":down_food_id,"down_sort":down_sort},
        async:true,
        dataType:"json",
        success:function(data){
            if(data.code == 1){
                $.ajax({
                    type:"get",
                    url:"/index.php/admin/dishes/showDisinfoBykey/category_id/"+food_category_id,
                    success:function(data){
                        $("#mytr").html(data);
                    },
                    error:function(){
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                });
            }else if(data.code == 2){
                layer.msg(vm.langData.lastChild[vm.lang]);
            }
        }
    });
}


//菜品数据上移(food表)
function moveup(obj) {
    var sort = $(obj).data('sort'); //排序ID
    var food_id = $(obj).data('food_id'); //菜品自增ID
    var when_tr = parseInt($(obj).data('index'));
    var page = $(".current").data('page'); //当前页数
    if (page == undefined || !page) {
        page = 1;
    }
    if (page == 1 && when_tr == 1) {
        return false;
    }
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/moveup",
        data: { "sort": sort, "food_id": food_id },
        dataType: "json",
        success: function(data) {
            if (data.code == 1) {
                $.ajax({
                    url: "/index.php/admin/Dishes/deskInfo/page/" + page,
                    type: "get",
                    success: function(data) {
                        $("#mytr").html(data);
                    },
                    error: function() {
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                });
            }else if(data.code == 2){
				layer.msg(vm.langData.lastChild[vm.lang]);
			}
        }
    });
}

//菜品数据下移(food表)
function movedown(obj) {
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var page = parseInt($(".current").html());
    var pageArr = new Array();
    $(".pagination").children().children('a').each(function(index, element) {
        var when_page = parseInt($(element).data('page'));
        pageArr[index] = when_page;
    });
    var max = pageArr[0]
    for (var i = 1; i < pageArr.length; i++) {
        if (pageArr[i] > max) {
            max = pageArr[i]; //获取最大页数
        }
    }
    var last_tr = $("#mytr").children().children('tr:last'); //获取最后一个tr
    var downObj = $(obj).parent(); //获取点击时的tr
    if (page == max && last_tr == downObj) { //如果当前页是最后一页且所点击的tr是最后一个tr，则中止操作
        return false;
    }

    if (page == undefined || !page) {
        page = 1;
    }

    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/movedown",
        data: { "sort": sort, "food_id": food_id },
        dataType: "json",
        success: function(data) {
            if (data.code == 1) {
                $.ajax({
                    url: "/index.php/admin/Dishes/deskInfo/page/" + page,
                    type: "get",
                    success: function(data) {
                        $("#mytr").html(data);
                        layer.msg(vm.langData.success[vm.lang]);
                    },
                    error: function() {
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                });
            }else if(data.code == 2){
				layer.msg(vm.langData.lastChild[vm.lang]);
			}
        }
    });
}

//点击菜品类表显示对应菜品信息		
function showinfo(obj) {
    var category_id = $(obj).data("id");
    $.ajax({
        type: "get",
        url: "/index.php/admin/dishes/showDisinfoBykey/category_id/" + category_id + "",
        success: function(data) {
            $('#mytr').html(data);
        }
    });
    $(obj).parents('tr').siblings().removeClass('active');
    $(obj).parents('tr').addClass('active');
    if($(obj).parents('tr').hasClass('active')) {
    	sessionStorage.setItem('status_id',category_id)
    }
}

function showinfoStatus(id) {
    $.ajax({
        type: "get",
        url: "/index.php/admin/dishes/showDisinfoBykey/category_id/" + id + "",
        success: function(data) {
            $('#mytr').html(data);
            var len = $('#mytype tbody tr');
            var category_id;
            for(var i = 0; i < len.length; i++) {
            	category_id = $(len[i]).attr('data-category_id');
            	if(id == category_id){
            		$(len[i]).addClass('active');
            	}
            }
        }
    });
}

//提交菜品分类模态框
function commit() {
    var hschek = $("input[name='is_timing']").is(':checked');
    // 判断是否开启定时。0：关闭，1：开启
    if (hschek) {
        status = 1;
    } else {
        status = 0;
    }

    if ($("#way").val() != 1) {
        // 新增菜品分类
        if ($("#category_name").val() == "") {
            layer.msg(vm.langData.dishesCategoryEmpty[vm.lang]);
        } else {
            var category_name = $("#category_name").val();
            var formdata = new FormData();
            formdata.append("category_name", category_name);
            formdata.append("category_english_name", $("#category_english_name").val());
            formdata.append("is_timing", status);

            // 图标URL
            var img_url = $("#img_url").val();
            formdata.append("img_url", img_url);
            // 自定义文件域
            formdata.append("user_define_img", $("#user_define_img")[0].files[0]);

            if (status == 1) {
                var timeInfo = $("#time").children();
                var dayInfo = $("#day").children();
                var dayInfoArray = new Array();
                $.each(dayInfo, function(k, v) {
                    dayInfoArray[k] = new Array();
                    var i = 0;
                    $.each($(v).children(), function(k1, v1) {
                        var length = $(v).children().length;
                        if ($(v1)[0].checked == true || k1 == 14 || k1 == 15) {
                            if (k1 == 14 || k1 == 15) {
                                dayInfoArray[k][i] = $(v1).children().val();
                            } else {
                                dayInfoArray[k][i] = $(v1).val();
                            }
                            i++;
                        }
                    });
//                    console.log($(v).children().length);
                });


                var timeInfoArray = new Array();
                $.each(timeInfo, function(k3, v3) {
                    timeInfoArray[k3] = new Array();
                    var j = 0;
                    $.each($(v3).children(), function(k4, v4) {
                        if (k4 == 0) {
                            var start_value = $(v4).children('input').val();
                            if (start_value != '') {
                                timeInfoArray[k3][j] = start_value;
                                j++;
                            }
                        } else {
                            if ($(v4).val() != "") {
                                timeInfoArray[k3][j] = $(v4).val();
                                j++;
                            }
                        }
                    });
                });

                timeInfoArray = JSON.stringify(timeInfoArray);
                dayInfoArray = JSON.stringify(dayInfoArray);
                formdata.append("time", timeInfoArray);
                formdata.append("day", dayInfoArray)
            }
            $.ajax({
                type: 'post',
                url: '/index.php/admin/Dishes/createDishetype',
                data: formdata,
                // dataType:"json",
                cache: false,
                processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
                contentType: false, // 不设置Content-type请求头
                success: function(data) {
                    $('#mytype').html(data);
                    // alert("新增成功！");
                    $("#classify-icon").attr('src', '/public/images/defaultFoodCate.png');

                    var file = $("#user_define_img")
                    file.after(file.clone().val(""));
                    file.remove();

                    $("input[type='reset']").trigger("click");
                }
            });
        }
    } else {
        // 编辑菜品分类
        var formdata = new FormData();
        formdata.append("restaurant_id", $("#restaurant_id").val());
        formdata.append("category_id", $("#category_id").val());
        formdata.append("category_name", $("#category_name").val());
        formdata.append("category_english_name", $("#category_english_name").val());
        formdata.append("is_timing", status);

        // 图标URL
        var img_url = $("#img_url").val();
        formdata.append("img_url", img_url);
        // 文件域
        formdata.append("user_define_img", $("#user_define_img")[0].files[0]);

        // if ($("#commitfile").val() != ""){
        //     var reader = new FileReader();
        //     reader.readAsDataURL($('#commitfile')[0].files[0]);
        //     formdata.append("file",$('#commitfile')[0].files[0]);
        // }
        if (status == 1) {
            var timeInfo = $("#time").children();
            var dayInfo = $("#day").children();
            var dayInfoArray = new Array();
            $.each(dayInfo, function(k, v) {
                dayInfoArray[k] = new Array();
                var i = 0;
                $.each($(v).children(), function(k1, v1) {
                    //console.log($(v1),k1);
                    var length = $(v).children().length;
                    if ($(v1)[0].checked == true || k1 == 14 || k1 == 15) {
                        if (k1 == 14 || k1 == 15) {
                            dayInfoArray[k][i] = $(v1).children().val();
                        } else {
                            dayInfoArray[k][i] = $(v1).val();
                        }
                        i++;
                    }
                });
                //console.log($(v).children().length);
            });


            var timeInfoArray = new Array();
            $.each(timeInfo, function(k3, v3) {
                timeInfoArray[k3] = new Array();
                var j = 0;
                $.each($(v3).children(), function(k4, v4) {
                    if (k4 == 0) {
                        var start_value = $(v4).children('input').val();
                        if (start_value != '') {
                            timeInfoArray[k3][j] = start_value;
                            j++;
                        }
                    } else {
                        if ($(v4).val() != "") {
                            timeInfoArray[k3][j] = $(v4).val();
                            j++;
                        }
                    }
                });
            });

            timeInfoArray = JSON.stringify(timeInfoArray);
            dayInfoArray = JSON.stringify(dayInfoArray);
            formdata.append("time", timeInfoArray);
            formdata.append("day", dayInfoArray)
        }

        $.ajax({
            type: 'post',
            url: '/index.php/admin/Dishes/modifyDishestype',
            data: formdata,
            // dataType:"json",
            cache: false,
            processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
            contentType: false, // 不设置Content-type请求头
            success: function(data) {
                $('#mytype').html(data);
                //alert("编辑成功！");
                //$("input[type='reset']").trigger("click");
                $("#classify-icon").attr('src', '/Public/images/defaultFoodCate.png');

                var file = $("#user_define_img")
                file.after(file.clone().val(""));
                file.remove();
            }
        });

    }
}

//模态框消失后清空表单
$('#addSort').on('hidden.bs.modal', function() {
    // 执行一些动作...
    $('#category_name').attr("value", "");
    $('#edit_upload_box').attr("src", "");
    $("input[type='reset']").trigger("click");
    $("#time").html("");
    $("#day").html("");
    $("#show2").hide();
})

//删除时间
function deletetime() {
    $('.dingtime').each(function(index, element) {
        $(element).remove((index));
    });
}

/*
 ===========================================================================================================
 */
function trigger() {
    triggerTime();
//  triggerDay();
}

function triggerTime() {
    $('.startTime').datetimepicker({
        format: 'yyyy-mm-dd hh:ii:00',
        language: 'zh-CN',
        pickDate: true,
        pickTime: true,
        autocolse: true,
        hourStep: 1,
        minuteStep: 1,
        secondStep: 30,
        inputMask: true,
        pickerPosition:'top-right'
    }).on("click", function(ev) {
        $(".startTime").datetimepicker("setEndDate", $(".endTime").val());
    });
    $('.endTime').datetimepicker({
        format: 'yyyy-mm-dd hh:ii:00',
        language: 'zh-CN',
        autocolse: true,
        pickDate: true,
        pickTime: true,
        hourStep: 1,
        minuteStep: 1,
        secondStep: 30,
        inputMask: true,
        pickerPosition:'top-right'
    }).on("click", function(ev) {
        $(".endTime").datetimepicker("setStartDate", $(".startTime").val());
    });
    
    
    $('.dayStartTime').datetimepicker({
        format: 'hh:ii',
        language: 'zh-CN',
        autocolse: true,
        pickDate: false,
        pickTime: true,
        hourStep: 1,
        minuteStep: 1,
        secondStep: 30,
        inputMask: true,
        startView: 1
    }).on("click", function(ev) {
        $(".dayStartTime").datetimepicker("setStartDate", $(".dayStartTime").val());
        $('.datetimepicker th.switch').css('display','none');
        $('.datetimepicker tr td span.minute').removeClass('disabled');
        $('.datetimepicker tr td span.hour').removeClass('disabled');
    });
    $('.dayEndTime').datetimepicker({
        format: 'hh:ii',
        language: 'zh-CN',
        autocolse: true,
        pickDate: false,
        pickTime: true,
        hourStep: 1,
        minuteStep: 1,
        secondStep: 30,
        inputMask: true,
        startView: 1
    }).on("click", function(ev) {
        $(".dayEndTime").datetimepicker("setEndDate", $(".dayEndTime").val());
        $('.datetimepicker th.switch').css('display','none');
        $('.datetimepicker tr td span.minute').removeClass('disabled');
        $('.datetimepicker tr td span.hour').removeClass('disabled');
    });
}

function triggerDay() {
    for (var i = 0; i < 24; i++) {
        if (i < 10) {
            /*$("#day").children(":last").find("select").append("<option onclick='assign()' value='0"+i+":00'>0"+i+":00</option>");  //添加一项option
            $("#day").children(":last").find("select").append("<option value='0"+i+":30'>0"+i+":30</option>");  //添加一项option*/
            $("#day").children(":last").find("select").append("<option value='0" + i + ":00'>0" + i + ":00</option>"); //添加一项option
            $("#day").children(":last").find("select").append("<option value='0" + i + ":30'>0" + i + ":30</option>"); //添加一项option
        } else {
            $("#day").children(":last").find("select").append("<option value='" + i + ":00'>" + i + ":00</option>"); //添加一项option
            $("#day").children(":last").find("select").append("<option value='" + i + ":30'>" + i + ":30</option>"); //添加一项option
        }
    }
}

function changeType(type) {
    $("#add-btn").data("type", type);
}

//添加时间段
function addTiming(obj) {
    var type = $(obj).data("type");
    if (type) {
        var str = '<div class="modal-item">\
	        				<div class="inline-block">\
	        					<label for="startTime">'+vm.langData.start[vm.lang]+':</label>\
	        					<input type="text" class="startTime selectIcon" id="startTime" name="startTime">\
	        				</div>\
	        				<label for="endTime">'+vm.langData.end[vm.lang]+':</label>\
	        				<input type="text" name="endTime" class="endTime selectIcon" id="endTime">\
	        				<button class="remove">\
	        					<img src="/Public/images/remove_circle.png">\
	        				</button>\
        				</div>';
        $("#time").append(str);
    } else {
        var str = '<div class="modal-item">\
							<input type="checkbox" name="monday" value="1"><label>'+vm.langData.Monday[vm.lang]+'</label>\
							<input type="checkbox" name="tuesday" value="2"><label>'+vm.langData.Tuesday[vm.lang]+'</label>\
							<input type="checkbox" name="wednesday" value="3"><label>'+vm.langData.Wednesday[vm.lang]+'</label>\
							<input type="checkbox" name="thursday" value="4"><label>'+vm.langData.Thursday[vm.lang]+'</label>\
							<input type="checkbox" name="friday" value="5"><label>'+vm.langData.Friday[vm.lang]+'</label>\
							<input type="checkbox" name="saturday" value="6"><label>'+vm.langData.Saturday[vm.lang]+'</label>\
							<input type="checkbox" name="sunday" value="0"><label>'+vm.langData.Sunday[vm.lang]+'</label>\
							<div class="inline-block" style="width:100px; margin-right:0">\
	        					<input type="text" class="dayStartTime selectIcon" id="dayStartTime" name="dayStartTime" style="width:100px" autocomplete="off">\
	        				</div> - \
							<div class="inline-block" style="width:100px; margin-right:0">\
	        					<input type="text" class="dayEndTime selectIcon" id="dayEndTime" name="dayEndTime" style="width:100px" autocomplete="off">\
	        				</div>\
							<button class="remove">\
								<img src="/Public/images/remove_circle.png">\
							</button>\
                        </div>';
        $("#day").append(str);
    }
    trigger();
    
                        $('.remove').click(function(){
							$(this).parent().remove();
						});
}

//点击页码执行动作
$("#detail-page").children().children("a").click(function() {
    var page = parseInt($(this).data("page"));
    $.ajax({
        url: "/index.php/admin/Dishes/deskInfo/page/" + page + "",
        type: "get",
        success: function(data) {
            $("#mytr").html(data);
        },
        error: function() {
            layer.msg(vm.langData.error[vm.lang]);
        }
    });
});

//点击页码执行动作
$("#detail-page-open").children().children("a").click(function() {
    var page = parseInt($(this).data("page"));
    $.ajax({
        url: "/index.php/admin/Dishes/deskOpenHualala/page/" + page + "",
        type: "get",
        success: function(data) {
            $("#mytr").html(data);
        },
        error: function() {
            layer.msg(vm.langData.error[vm.lang]);
        }
    });
});

// 点菜品编辑跳到指定编辑页且传递一个当前页数
// 是否有权限控制
function modify_food(obj) {
    var food_id = $(obj).data('food_id');
    var type = $(obj).data('type');
    var category_id = $(obj).data('category_id');
	var page = $(".current").data('page'); //当前页数
	if (page == undefined) {
	   page = 1;
	}
    if (category_id == undefined) {
        category_id = 0;
    }
    location.href = "/index.php/admin/Dishes/edit/food_id/" + food_id + "/category_id/" + category_id + "/type/" + type +"/page/" + page;
}