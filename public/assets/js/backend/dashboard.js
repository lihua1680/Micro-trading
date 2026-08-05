define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template', 'form'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template, Form) {

    var Controller = {
        index: function () {
        	
        	// 基于准备好的dom，初始化echarts实例
            var myChart1 = Echarts.init(document.getElementById('echart1'), 'walden');

            var option1 = {
				    color: ['#FF0000', '#0000FF'],
				    tooltip: {
				        trigger: 'axis',
				        axisPointer: {
				            type: 'shadow'
				        }
				    },
	                legend: {
	                    data: ['CNY充值', 'CNY提现','USDT充值', 'USDT提现']
	                },
	                toolbox: {
	                    show: true,
	                    feature: {
	                        magicType: {show: true, type: ['stack', 'tiled']},
	                        saveAsImage: {show: true}
	                    }
	                },
				    grid: {
				        left: '0%',
				        right: '0%',
				        bottom: '10%',
				        top:'10%',
				        containLabel: true
				    },
				    calculable: true,
				    xAxis: [
				        {
				            type: 'category',
				            axisTick: {show: false},
				            data: chatdata.timeList
				        }
				    ],
				    yAxis: [
				        {
				            type: 'value'
				        }
				    ],
				    series: [
				        {
				            name: 'CNY充值',
				            type: 'bar',
				            barGap: 0,
				            data: chatdata.mark.up
				        },
				        {
				            name: 'CNY提现',
				            type: 'bar',
				            data: chatdata.mark.down
				        },{
				            name: 'USDT充值',
				            type: 'bar',
				            barGap: 0,
				            data: chatdata.mark.usdtup
				        },
				        {
				            name: 'USDT提现',
				            type: 'bar',
				            data: chatdata.mark.usdtdown
				        }
				    ]
				};

            // 使用刚指定的配置项和数据显示图表。
            myChart1.setOption(option1);
            
            
         // 基于准备好的dom，初始化echarts实例
            var myChart2 = Echarts.init(document.getElementById('echart2'), 'walden');

            var option2 = {
				    color: ['#FF0000', '#008000'],
				    tooltip: {
				        trigger: 'axis',
				        axisPointer: {
				            type: 'shadow'
				        }
				    },
	                legend: {
	                    data: ['买涨', '买跌']
	                },
	                toolbox: {
	                    show: true,
	                    feature: {
	                        magicType: {show: true, type: ['stack', 'tiled']},
	                        saveAsImage: {show: true}
	                    }
	                },
				    grid: {
				        left: '0%',
				        right: '0%',
				        bottom: '10%',
				        top:'10%',
				        containLabel: true
				    },
				    calculable: true,
				    xAxis: [
				        {
				            type: 'category',
				            axisTick: {show: false},
				            data: chatdata.timeList
				        }
				    ],
				    yAxis: [
				        {
				            type: 'value'
				        }
				    ],
				    series: [
				        {
				            name: '买涨',
				            type: 'bar',
				            barGap: 0,
				            data: chatdata.order.buy
				        },
				        {
				            name: '买跌',
				            type: 'bar',
				            data: chatdata.order.sell
				        }
				    ]
				};

            // 使用刚指定的配置项和数据显示图表。
            myChart2.setOption(option2);

  

            $(document).on("click", ".btn-checkversion", function () {
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });


        
        	Form.api.bindevent("form",function(data, ret){
            	$.each(data,function(i,val){
            		$("#"+i).text(val);
            	});
            });
        	
        	//Form.api.submit("form");
            $(document).on("click", ".btn-checkversion", function () {
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });

        }
    };

    return Controller;
});