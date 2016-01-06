window.addEventListener('touchmove', function () {
    event.preventDefault();
})

window.onload = init;

function init(){
    var windowWidth;
    var windowHeight;
    var gameCanvas,context,buffer,bufferCtx;
    var loadIntervel,personIntervel,treeIntervel,fingerIntervel,startIntervel,secondIntervel;
    var meter = 0;
    var time = 100;
    var flag = false;
	var fontarrnum = 0
	var fontarr = ['别发呆了起来嗨！','这速度不会垫底的，大概倒数第二吧','前面有漂亮妹子，冲！冲！冲！',
                    '地上有钱诶，不停下来捡吗？','随风奔跑自由是方向~~~',
                    '前方高能预警，请加速╭(′▽)╯','跑太快要脱离地球引力了！',
                    '太快了语无伦次，传说中的快枪手？！','你这么牛逼，你妈妈知道吗？',
                    '没有更快了！请收下我的膝盖！'];

    var loadImg = new Image();
    var personImg = new Image();
    var personsImg = new Image();
    var treeImage = new Image
	var sky = new Image();
    var green = new Image();
    var road = new Image();
    var sun = new Image();
    var cloud1 = new Image();
    var cloud2 = new Image();
    var cloud3 = new Image();

    windowWidth = document.documentElement.clientWidth;
    windowHeight = document.documentElement.clientHeight;


    GetObj('gameCanvas').width = windowWidth;
    GetObj('gameCanvas').height = windowHeight;

    gameCanvas = GetObj('gameCanvas');
    context = gameCanvas.getContext("2d");

    buffer = document.createElement("canvas");
    buffer.width = GetObj('gameCanvas').width;
    buffer.height = GetObj('gameCanvas').height;
    bufferCtx = buffer.getContext("2d");

    context.clearRect(0,0,GetObj('gameCanvas').width,GetObj('gameCanvas').height);
    bufferCtx.clearRect(0,0,buffer.width,buffer.height);

    var second = parseInt($('#second span').text());


    loadImg.addEventListener('load', eventLoaded, false);
    loadImg.src = "./../addons/wmb_run/template/mobile/img/loading.png";
    personImg.src = "./../addons/wmb_run/template/mobile/img/per_other.png";
    personsImg.src = "./../addons/wmb_run/template/mobile/img/persons.png";
    treeImage.src = "./../addons/wmb_run/template/mobile/img/tree.png";
	green.src = "../addons/wmb_run/template/mobile/img/green.png";
    road.src = "../addons/wmb_run/template/mobile/img/black.png";
    sun.src = "../addons/wmb_run/template/mobile/img/sun.png";
    cloud1.src = "../addons/wmb_run/template/mobile/img/cloud1.png";
    cloud2.src = "../addons/wmb_run/template/mobile/img/cloud2.png";
    cloud3.src = "../addons/wmb_run/template/mobile/img/cloud3.png";

    clearInterval(fingerIntervel);
    function eventLoaded() {
        drawLoad(0);
        var imagex = 66;
        loadIntervel = setInterval(function(){
            if(imagex > 660){
//                imagex = 0;
                setTimeout(function(){
                    clearInterval(loadIntervel);
                    context.clearRect(0,0,windowWidth,windowHeight);
                    context.fillStyle = "#c5f3ff";
                    context.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
                    ready();
                },10)
            }
            drawLoad(imagex);
            imagex+=66;
        },50)
    }

    function personLoaded(){
        drawPerson();
    }

    var personx = 125;
    var treex = 290;var d = 0;
//    draw(0,0);
    function start(time){
        startIntervel = setInterval(function(){
            if(personx > 875){
                personx = 875;
                d++;
            }else if(personx == 0){
                personx = 0;
                d++;
            }
            if(treex > 3744){
                treex = 0;
            }
            draw(personx,treex);
            ((d+1)%2 ==0) ? personx-=125 : personx+=125;
            treex+=288;
            meter+=0.3;
            $('#score span').text(parseInt(meter)+'m');
        },time)

    }



    function drawLoad(imgX) {
        context.beginPath();
        context.fillStyle = "white";
        context.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
        context.drawImage(loadImg,imgX,0,66,120,windowWidth/2-33,windowHeight/2-60,66,120);
        context.font="14px Arial";
        context.fillStyle="orange";
        context.fillText("loading...",windowWidth/2-33,windowHeight/2+74);
        context.stroke();
    }

    function drawPerson(){
        drawBuffer(personImg,0,0,366,520,windowWidth/2-90,windowHeight/2-60,180,260);
        context.drawImage(buffer,0,0);
    }
    function drawPersonTwo(){
        drawBuffer(personImg,366,0,366,520,windowWidth/2-90,windowHeight/2-120,180,260);
        context.drawImage(buffer,0,0);
    }

    function draw(personx,treex){

        context.clearRect(0,0,GetObj('gameCanvas').width,GetObj('gameCanvas').height);
        bufferCtx.clearRect(0,0,buffer.width,buffer.height);

        context.fillStyle = "#c5f3ff";
        context.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
		
		drawsun();
        drawcloud();
        drawgreen();
        drawroad();

        drawBuffer(treeImage,treex,0,288,315,windowWidth/2-145,windowHeight/2-157.5,288,315);
        drawBuffer(personsImg,personx,0,125,275,windowWidth/2-65,windowHeight/2-120,125,275);
        context.drawImage(buffer,0,0);
    }

	function drawgreen(){
        drawBuffer(green,0,0,640,464,0,windowHeight/2-157.5+50,windowWidth,464);
        context.drawImage(buffer,0,0);
    }

    function drawroad(){
        drawBuffer(road,0,0,640,625,0,windowHeight/2-157.5+50,windowWidth,625);
        context.drawImage(buffer,0,0);
    }

    function drawsun(){
        drawBuffer(sun,0,0,231,93,windowWidth/2-(231/2)/2,windowHeight/2-135,231/2,93/2);
        context.drawImage(buffer,0,0);
    }

    function drawcloud(){
        drawBuffer(cloud1,0,0,254,120,windowWidth/2-(254/2)/2-254/2,windowHeight/2-200,254/2,120/2);
        drawBuffer(cloud2,0,0,163,59,windowWidth/2-(163/2)/2,windowHeight/2-200,163/2,59/2);
        drawBuffer(cloud3,0,0,228,107,windowWidth/2+(107/2)/2,windowHeight/2-200,228/2,107/2);
        context.drawImage(buffer,0,0);
    }
	
    function drawBuffer(img,sx,sy,swidth,sheight,moveWt,moveHt,w,h){
        bufferCtx.drawImage(img,sx,sy,swidth,sheight,moveWt,moveHt,w,h);
    }


    function ready(){
        $('#ready,#finger,.g-pace').show();
		drawsun();
        drawcloud();
        drawgreen();
        drawroad();
        drawPerson();
        drawBuffer(treeImage,0,0,288,315,windowWidth/2-145,windowHeight/2-157.5,288,315);
        context.drawImage(buffer,0,0);
        var fingerNum = 0;
        fingerIntervel = setInterval(function(){
            $('#finger span').removeClass('on');
            $('#finger span').eq(fingerNum).addClass('on');
            if(fingerNum > 1){
                fingerNum = 0;
            }else{
                fingerNum++;
            }
        },100)

        $('#finger #start').bind('touchstart',function(){
            clearInterval(fingerIntervel);
            $('#finger span').removeClass('on');
            $('#ready').hide();
            $('#finger').remove();
            $('#second,#score').show();

            secondIntervel = setInterval(function(){
                $('#second span').text(second+'s');
                if(second <= 0){
                    clearInterval(startIntervel);
                    flag = false;
					clearInterval(secondIntervel);
					gameover();
                }else{
                    second--;
					$('#g-voiceover').text(fontarr[fontarrnum]);
					fontarrnum++;
                }
            },1000)
            start(time);
            $(this).remove();
            flag = true;
        })

        $('.g-pace a').bind('touchstart',function(){
            $('.g-pace a').removeClass('sel');
            $(this).addClass('sel');
            if(flag == true){
                clearInterval(startIntervel);
                if(time <= 30){
                    time = 30;
                }else{
                    time -= 5;
                }
                start(time);
                //console.log(time);
            }
        })

    }

    function gameover(){
        context.clearRect(0,0,GetObj('gameCanvas').width,GetObj('gameCanvas').height);
        bufferCtx.clearRect(0,0,buffer.width,buffer.height);
        context.fillStyle = "#c5f3ff";
        context.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
		drawsun();
        drawcloud();
        drawgreen();
        drawroad();
        drawPersonTwo();
        drawBuffer(treeImage,0,0,288,315,windowWidth/2-145,windowHeight/2-157.5,288,315);
        context.drawImage(buffer,0,0);
		console.log(1111111)
		if(meter){
			
			/*var url =$("#runremurl").val();
			var urlshare =$("#shareurl").val();
			var shareurli =$("#shareurli").val();
			var url=url+"&dis="+meter;
				 $.ajax({
					 type: "POST",
					 url: url,
					 success: function(data){
					 //console.log(data);
					 var data = eval("("+data+")");
					alert(data.misu);
						 if(data.misu>0) //成功
						 {
						// alert("保存记录成功。");
						 if(data.stuts==1){
							window.location.href = urlshare;
						 }else{
							window.location.href = shareurli;
						 }

						 // location.reload();
						 }
						 else{
							alert("保存记录失败。");
						 }

					 }
				});*/
		// window.location.href=url+"&dis="+meter;	
			var url =$("#runremurl").val();
			 window.location.href=url+"&dis="+meter;	
			
		}
		
	
    }
}

function GetObj(id) {
    var tagArr = ['body','div','ul','ol','li','i','b','p','span','label'];
    for(var i = 0;i<tagArr.length;i++){
        if(id != tagArr[i]){
            var obj = document.getElementById(id);
            return obj;
        }else{
            var obj = document.getElementsByTagName(id)[0];
            return obj;
        }
    }
}

