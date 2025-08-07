/**
* Tracker 2.1.0
* 
* Projects Javascript
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/
ACPProjectStats = {
	ticks: {
		15: '19 Aug',
		30: '22 Aug',
		45: '25 Aug',
		60: '28 Aug',
		75: '31 Aug',
		90: '02 Sep',
		105: '05 Sep',
		120: '08 Sep',
		135: '11 Sep',
		150: '14 Sep'
	},
	init: function()
	{
		Debug.write("Initializing acp.project-stats.js");
		
		document.observe( 'dom:loaded', function()
			{
				var a = Flotr.draw( $('piechart-statuses'),
					[
						{data:[[0,6]], label:"Confirmed - Skin Issue"},
						{data:[[0,4]], label:"New Report"}
					],
					{
						pie: {show: true},
						legend: {position: 'nw', labelFormatter: ACPProjectStats.graphLabels },
						grid: {
							color: '#000',
							verticalLines: false, 
							horizontalLines: false,
							outlineWidth: 0
						},
						xaxis: {showLabels: false},
						yaxis: {showLabels: false}
					}
				);
				
				var b = Flotr.draw( $('piechart-severities'),
					[
						{data:[[0,1.32]], label:"5 - Critical (132 issues)"},
						{data:[[0,2]], label:"4 - High (200 issues)"},
						{data:[[0,0.3]], label:"3 - Medium (30 issues)"},
						{data:[[0,1]], label:"2 - Fair (100 issues)"},
						{data:[[0,1.38]], label:"1 - Low (138 issues)"},
						{data:[[0,4]], label:"0 - None (400 issues)", color:'#110066'}
					],
					{
						pie: {show: true},
						legend: {position: 'nw', labelFormatter: ACPProjectStats.graphLabels },
						grid: {
							color: '#000',
							verticalLines: false, 
							horizontalLines: false,
							outlineWidth: 0
						},
						xaxis: {showLabels: false},
						yaxis: {showLabels: false}, 
					}
				);
				
				var c = Flotr.draw( $('linegraph-rateoffix'),
					[
						{data:[[5,5],[10,4],[15,7],[20,8],[25,0],[30,0],[35,14],[40,5],[45,4],[50,7],[55,5],[60,4],[65,7],[70,8],[75,0],[80,0],[85,14],[90,5],[95,4],[100,7],[105,5],[110,4],[115,7],[120,8],[125,0],[130,0],[135,14],[140,5],[145,4],[150,7]], label:"Fixed"},
						{data:[[5,8],[10,9],[15,10],[20,8],[25,7],[30,5],[35,4],[40,2],[45,4],[50,5],[55,9],[60,14],[65,17],[70,20],[75,20],[80,15],[85,12],[90,7],[95,4],[100,0],[105,8],[110,8],[115,15],[120,8],[125,0],[130,0],[135,1],[140,4],[145,2],[150,7]], label:"Incoming"}
					],
					{
						legend: {position: 'nw', labelFormatter: ACPProjectStats.graphLabels, backgroundColor: '#D2E8FF', labelBoxBorderColor:'#cccccc' },
						grid: {
							color: '#000',
							outlineWidth: 0
						},
						yaxis: {
							min: -5,
							noTicks: 20,
							tickFormatter: ACPProjectStats.valueTickFormatter,
							tickDecimals: 0,
						},
						xaxis: {
							noTicks: 30,
							tickDecimals: 0,
							tickFormatter: ACPProjectStats.monthTickFormatter
						},
						lines: {
							show: true,
						},
						points: {
							show: true,
						}
					} 
				);
			}
		);
	},
	
	valueTickFormatter: function(tick)
	{
		if ( tick % 3 == 0 )
		{
			if ( tick < 0 )
			{
				return '';
			}
			
			return tick;
		}		
		
		return '';
	},
	
	monthTickFormatter: function(tick)
	{
		if ( tick % 3 == 0 )
		{
			return ACPProjectStats.ticks[tick];
		}
		
		return '';
	},
	
	graphLabels: function(label)
	{
		return label;
	}
};

ACPProjectStats.init();