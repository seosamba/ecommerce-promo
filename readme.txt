1) Magic spaces "Onsale".
Hide promo-text in products which do not have promotions.
Use on product page: {onsale}promo-text{/onsale}.
Use on product listing template: {onsale:{$product:id}}promo-text{/onsale}.

2) Flip clock widget:
 This widget show countdown timer.
 Just put it in container or template {$flipclock}

 Example widget with all options:
 {$flipclock:zoom:0.5:metric-color:black:time-color:#cd092f:diliver-color:#cd092f:time-background:white:labels:disable}

 Options description:
 For colors you can use color names or HTML color codes, not rgb. example (green or #008000).

 1. zoom - allowed values from 0.1 to 1.9
 2. metric-color - set color for days/hours/minutes/seconds labels
 3. time-color - set color for numbers
 4. time-background - set color for countdown timer
 5. diliver-color - set color for dots
 6. labels - allow use one option "disable". If this option = disable the days/hours/minutes/seconds labels names not shown.