/**
 * Created by Administrator on 2016/11/16.
 */
function submitPayInfo(obj){
    var type = $(obj).data("paytype");
    console.log(type);
    var formData;
    if(type == 'wxpay'){
        var formName1 = "wxpayForm";
        var form1     = $("#"+formName1)[0];
        formData      = new FormData(form1);
    }else if(type == 'ali_face'){
        formData = new FormData($("#aliFaceForm")[0]);
    }else if(type == 'others'){
        var formName3 = "othersForm";
        var form3     = $("#"+formName3)[0];
        formData = new FormData(form3);
    }else if (type == "oy_wxpay") {
        var formName4 = "oy_wxpayForm";
        var form4     = $("#"+formName4)[0];
        formData      = new FormData(form4);
    }else if (type == "oy_alipay") {
        var formName5 = "oy_alipayForm";
        var form5     = $("#"+formName5)[0];
        formData      = new FormData(form5);
    }else if(type == "shandepay"){
        var formName6 = "shandeForm";
        var form6     = $("#"+formName6)[0];
        formData      = new FormData(form6);
    }else{
        var formName2 = "alipayForm";
        var form2 = $("#"+formName2)[0];
        formData = new FormData(form2);
    }
    console.log(formData);
    $.ajax({
        url:"/index.php/admin/RestaurantSetting/editAddPayInfo/type/"+type,
        data:formData,
        type:'post',
        dataType:'text',
        contentType:false,
        cache:false,
        processData:false,
        success:function(data){
            layer.msg(vm.langData.success[vm.lang]);
        },
        error:function(){
            layer.msg(vm.langData.networkError[vm.lang]);
        }
    });
}
function certUpload(file,filename,id){
    var prevDiv = document.getElementById(id);
    if (file.files && file.files[0]) {
        console.log(file.files);
        var file_size = file.files[0].size;
        if(file_size/1024<1024){
            file_size = (file_size/1024).toFixed(1)+"K";
        }else if(file_size>=1024*1024){
            file_size = ((file_size/1024)/1024).toFixed(1)+"M";
        }else if(file_size>=1024*1024*1024){
            file_size = ((file_size/1024)/1024/1024).toFixed(1)+"G";
        }
        prevDiv.innerHTML = file.files[0].name+" --- "+file_size;
    }

    var formdata = new FormData();
    formdata.append(filename, file.files[0]); //上传文件
    formdata.append("id",$("#restaurant_id").val());

    $.ajax({
        type: 'post',
        url: '/index.php/api/Refuse/file',
        data: formdata,
        cache: false,
        dataType:'json',
        processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
        contentType: false, // 不设置Content-type请求头
        success: function(data) {
            console.log(data);
            if(data.status == '1'){
                layer.msg(vm.langData.success[vm.lang]);
            }else {
                layer.msg(vm.langData.failed[vm.lang]);
            }


        }
    });
}
function submitPayInfos(obj){
    var formData;
    var formName3 = "othersForm";
    var form3 = $("#"+formName3)[0];
    formData = new FormData(form3);

    $.ajax({
        url:"/index.php/admin/RestaurantSetting/editAddPayInfos",
        data:formData,
        type:'post',
        dataType:'json',
        contentType:false,
        cache:false,
        processData:false,
        success:function(data){
            if(data == 1){
                layer.msg(vm.langData.success[vm.lang]);
            }else{
                layer.msg(vm.langData.failed[vm.lang]);
            }
        }
    });
}