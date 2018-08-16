<?php
/********支付配置******/
return [
    //PC支付宝
    0=>[
        'code' =>'alipay',
        'name' =>'PC端支付宝',
        'version' =>'1.0',
        'author' =>'fzq',
        'config' =>'a:6:{i:0;a:4:{s:4:"name";s:14:"alipay_account";s:5:"label";s:15:"支付宝帐户";s:4:"type";s:4:"text";s:5:"value";s:0:"";}i:1;a:4:{s:4:"name";s:10:"alipay_key";s:5:"label";s:21:"交易安全校验码";s:4:"type";s:4:"text";s:5:"value";s:0:"";}i:2;a:4:{s:4:"name";s:14:"alipay_partner";s:5:"label";s:17:"合作者身份ID";s:4:"type";s:4:"text";s:5:"value";s:0:"";}i:3;a:4:{s:4:"name";s:18:"alipay_private_key";s:5:"label";s:6:"秘钥";s:4:"type";s:8:"textarea";s:5:"value";s:0:"";}i:4;a:4:{s:4:"name";s:17:"alipay_pay_method";s:5:"label";s:19:" 选择接口类型";s:4:"type";s:6:"select";s:6:"option";a:2:{i:0;s:24:"使用担保交易接口";i:1;s:30:"使用即时到帐交易接口";}}i:5;a:4:{s:4:"name";s:7:"is_bank";s:5:"label";s:18:"是否开通网银";s:4:"type";s:6:"select";s:6:"option";a:2:{i:0;s:3:"否";i:1;s:3:"是";}}}',
        'config_value' =>'a:6:{s:14:"alipay_account";s:16:"lzsk2018@163.com";s:10:"alipay_key";s:32:"zpsfqihohgi41wn9sgccvbet8b7vptg2";s:14:"alipay_partner";s:16:"2088921983334411";s:18:"alipay_private_key";s:1586:"IEogIBAAKCAQEAyQNuR3UnMkcguFu29dM3oWXJFNmXw9mwcgYasnoMZ+lWIzQ7FJ6VRdkqZEY2YW67L8dvOtbEAmP8XT3x0ZkM/k4fBXurMNVCUJLyMtv2t/5SmQvpzJEIg0/zVsR32wbwOiv2V4eXY6nJ2/V66WfzLYHdIAV+VpE+WqZGL9ZbHGBw+qlShHC66aER/ys7KHLS0HcIUuD4HwAbewSgMm095TlmkSxW/M/pv7+berpWoyBtG8ek/VB34zBor9qexnEd1OPwTyhub4LHVpmQtITMh+o32GmGuqkqCiiGGg0rKAUsfxYGS2JArUZQfhmYuR1oNspRIJjIHs7PmNPKE/qFdQIDAQABAoIBAAKYRr4pZv4hKOz4Uh5OQbOVqsjYHjtq/foa1hFjvfFBc9k0fWbAvVCPgxqURXNwiH4PZwItb45QWBukBxEblq1ZuNDLTeRtfFOp8fJyIHczk0Fig/weCywWxh9ineF373Xwx1dN6shgkriCLLoCm4ME4CPfrkfTzChDaTiI3dg56JbK+lyskIAZLo2sArw2+oSpF1Ud8UYAS+/4Dn8FZN8riqAYvFDxs0SYCQ+QXHwAOqlyYsqBcmQf2zHPUxttCY2WC2ycRbsT+CYRPnguKREGYmeaYhqC1c0C3dShXWykJ9gHvW+tJZ0ySyqOY7cyW6cDUmIQ8/YD+aIIwlgMqGkCgYEA/aBgYaouJGlxCsC+t4W7f8cpNdaohK290N02COZXgi8CWl5P8AoVVZJgr80p47BVoMCO0dUd8lwIFVzRqeYgG+AyZyaBSdo/2RhSJtrwCfPnRQcHualg/uzO0sE00PGGlNkLIEVjEVHduKVBHT1zw2Zrn8OzbYdNFexMiU4gQNsCgYEAyuUByGjOo51VWoDTRnEtHeNLZ0079sm/li+piHI+61oZdGcaAfn8e0ZlE74UXkMRZ/20Nr7SBfXVnnhmfDbRA5UxiFr588IjFsfX5OFCE1EAGFgaeeJlupgBFiWlf7vECy+Mkq6r4cmVRK1n1S2RyLYNLGdin0qqYU/QXi7au+8CgYBTlG9OoANWsnyzG3N+DQ8N+7bj7Bpaptq/orZ01luOJimzWBMlulzvdy5voL+mLez57ZrjIUUeIh/z0kOxpol8RjS182V1zftucmpYLZwe24aiI7/y+hNhzu4VW+Ao3v8HvELDXrpX8c+MUsETfrXQdccPvjE57fWEPPu338wXMwKBgCydXN3/xeiOcTVcfJjRzDT1RSBrCFOFG37f7iyVyqYgAtbo0Pw8RzuoSBOmSX5MyygNzrH3FeG/pACbwxVvTFi4VtMABlSGjQa2XQ/0RH5Q4m93+CJzGSGFyT9gedrzo/E9vHCIvj0BAbS/WMS0p4j2F0F7XmFensaVMWF0dh67AoGAAnMeXXyTba6HA3+XpmFC2ANqFp2ARNQAVhWpkDKIiFRayaESGMx7HfL2OH8TnjakL8lGQMXSfK3tsH32cH/NSG/DZeBpI8x21jblQ+pZGBo0XG8tBaZL6wk5IdiM2D5y4It8o83pVd2eEItauoKFtb7wjDocUW+UpPR6gEdsXpw=";s:17:"alipay_pay_method";s:1:"2";s:7:"is_bank";s:1:"0";}',
        'desc' =>'PC端支付宝',
        'status' => 1,//0未启用 1启用
        'type'=>'payment', //不写 没图片
        'icon' => 'logo.jpg',
        'bank_code' =>'',
        'scene' =>2, //0 PC+手机  1手机  2PC
    ],
    //快钱支付
    1=>[
        'code' =>'billpay',
        'name' =>'快钱',
        'version' =>'1.0',
        'author' =>'fzq',
        'config' =>'a:25:{s:14:"merchantAcctId";s:13:"1020996924601";s:12:"inputCharset";s:1:"1";s:7:"pageUrl";s:0:"";s:5:"bgUrl";s:47:"http://118.31.42.205/futao/rmb_demo/recieve.php";s:7:"version";s:4:"v2.0";s:8:"language";s:1:"1";s:8:"signType";s:1:"4";s:9:"payerName";s:0:"";s:16:"payerContactType";s:1:"1";s:12:"payerContact";s:14:"2532987@qq.com";s:7:"orderId";s:14:"20171101092653";s:11:"orderAmount";s:1:"1";s:9:"orderTime";s:14:"20171101092653";s:11:"productName";s:6:"苹果";s:10:"productNum";s:1:"5";s:9:"productId";s:8:"55558888";s:11:"productDesc";s:0:"";s:4:"ext1";s:0:"";s:4:"ext2";s:0:"";s:7:"payType";s:2:"00";s:6:"bankId";s:0:"";s:8:"redoFlag";s:0:"";s:3:"pid";s:11:"10209969246";s:7:"signMsg";s:0:"";s:11:"payment_url";s:57:"https://www.99bill.com/gateway/recvMerchantInfoAction.htm";}',
        'config_value'=>'',
        'desc' =>'快钱支付',
        'status' => 0,//0未启用 1启用
        'type'=>'payment',
        'icon' => 'logo.jpg',
        'bank_code' =>'',
        'scene' =>2, //0 PC+手机 1手机 2PC
    ],
    //手机网站支付宝(WAP)
    2=>[
        'code' =>'alipayMobile',
        'name' =>'手机网站支付宝',
        'version' =>'1.0',
        'author' =>'fzq',
        'config' =>'a:6:{i:0;a:4:{s:4:"name";s:14:"alipay_account";s:5:"label";s:15:"支付宝帐户";s:4:"type";s:4:"text";s:5:"value";s:0:"";}i:1;a:4:{s:4:"name";s:10:"alipay_key";s:5:"label";s:21:"交易安全校验码";s:4:"type";s:4:"text";s:5:"value";s:0:"";}i:2;a:4:{s:4:"name";s:14:"alipay_partner";s:5:"label";s:17:"合作者身份ID";s:4:"type";s:4:"text";s:5:"value";s:0:"";}i:3;a:4:{s:4:"name";s:18:"alipay_private_key";s:5:"label";s:6:"秘钥";s:4:"type";s:8:"textarea";s:5:"value";s:0:"";}i:4;a:4:{s:4:"name";s:17:"alipay_pay_method";s:5:"label";s:19:" 选择接口类型";s:4:"type";s:6:"select";s:6:"option";a:2:{i:0;s:24:"使用担保交易接口";i:1;s:30:"使用即时到帐交易接口";}}i:5;a:4:{s:4:"name";s:7:"is_bank";s:5:"label";s:18:"是否开通网银";s:4:"type";s:6:"select";s:6:"option";a:2:{i:0;s:3:"否";i:1;s:3:"是";}}}',
        'config_value'=>'a:6:{s:14:"alipay_account";s:16:"lzsk2018@163.com";s:10:"alipay_key";s:32:"zpsfqihohgi41wn9sgccvbet8b7vptg2";s:14:"alipay_partner";s:16:"2088921983334411";s:18:"alipay_private_key";s:1592:"MIIEpAIBAAKCAQEAtzxP98JyP/2+4tr/LGpx5qKgMA8saKj/qUYJ08ra21Dp9VN1LAHFIVIh4yG/dxVW36INxrAL4tolldajSDOGJDOYm071aPipGeTNquRE2ct4BHjOGaj/I32403yMctrr+u+lFh9SM0G+ZXreXxsbXa2/Z1Y28ZInzb91qolUcFGCl/dZWJRuegZ4w+eU5u5nB1fy0v3cUgvOVhlZGI5oB6aj0/azXeBxBSb5VUKMPJRaj8Oe19dIM1jE6YRWHqgnkO78bOSJ9Y0Zpakl+bpJZ1zY8Q/zvTl+Vq1CGbVD+L/eGJVEpwI44VzO9xHCmtRfAEmJi0WhTiL4/zdHHuabhQIDAQABAoIBABsv+72LQGB2SehnDg2NDbFm19XJqpEs4iI/nh2qr6Zy7wPTikMpUBKNmTGWRE5rACTWaqzcWicirwj4e+mum2yrqy0AHjGhE5Yf+NQuYnjeU8R2GD5+cLzXXqEijcRM706gWCJcK1onmxR7kpsR7pGLwiaXCDf3s6g3jEHtdnXjoezCMszSx/lWL7r9NqGv7z89oLCHdLYn7EF2gdz1n2COaAlhRQmgFfbmPb0lSojUSgogxZ6+A402m4c6iTduFV2QQM9HZ1IHJpTvPcoaLT0j9VP49RVasE4wed+MDRgcZtJQaktTBUdCCNBoOtBTklYDHOmtJ0pkTW2h+EregoECgYEA3snya2s4WU1+xEXtNjr1IIA7OA2faH+Zj4qwLX3UEbRzNir+/SDkWj0fPB6yIYJAEKEU7StjRCBRCjwVcZmjKRiJ+cJf80RZuDImeSk7dwg9jBHcuMSs1MRCKbe8zNRUAz0qh7oj5zzM//aYLuBJ/t8aOhmePx6o3O0txneshCkCgYEA0ozvxkCdj/VK6kXTJzRHLXyO/Smd62hizptRfn6f5XwDSaad0mJWxMY8D0NU7Vt6pm7fJEKGR2Kwuf9OqDuItiJRC4YufB4FxFTsb7OJeZ2/gwBPvZhpIWM4cPHfs5V7JwcIroFQ4GDeJ5sJjS9eLpgJo5AjaLn3N+z8LunP5/0CgYBpEP4VcKRLYUOmVn/vMlDF+hNzdOE3i+2khzhcy9mGW/51jkNgpvFqKScg2C0TpnSGIyFWVD7lMwRk+j72qwkKaXswYV2UwSg6uNPyEeLsWOOFuirIrGABm2jEedU9F+li+aakCcHC3KalE+tjN/1NiHO68LfzdocWYXhT/75JOQKBgQCCxQYnG/rAbpkY6EU9FDshBTvKAQ8UJsE3kUAMUJj+7wcbt2BOSsWZcYXb9PXKdga3WCU/YBIGREV/QBKaal9+v9GWVsCIVh0+04AF4HyCDHfl5UuhdgfVtMpZW+CUqULT+opp1+djdMaF0sUb60+ToNpbvCpB4T6qfYYjnRSEAQKBgQCSK4nlmZaQ9rDvSiZlFWXMbmIWQswZzFDd+Ego5zIprxt7HvxBsK9daGWxdDpKqpY2lddPrS8nLDXTdHyiUxLnfQmimOhKZeat75JfxPB1cqCtFrmxuDNyVlnPbFrZM96KX4E+UYod/qvwz6mnXp9G/FdFRC5hFDYUwI0qRs2VjA==";s:17:"alipay_pay_method";s:1:"1";s:7:"is_bank";s:1:"0";}',
        'desc' =>'手机端网站支付宝支付',
        'status' => 1,//0未启用 1启用
        'type'=>'payment', //不写 没图片
        'icon' => 'logo.jpg',
        'bank_code' =>'',
        'scene' =>1, //0 PC+手机  1手机  2PC
    ],

    //快钱手机支付
    3=>[
        'code' =>'billpayMobile',
        'name' =>'快钱手机',
        'version' =>'1.0',
        'author' =>'fzq',
        'config' =>'a:25:{s:14:"merchantAcctId";s:13:"1020996924601";s:12:"inputCharset";s:1:"1";s:7:"pageUrl";s:0:"";s:5:"bgUrl";s:47:"http://118.31.42.205/futao/rmb_demo/recieve.php";s:7:"version";s:4:"v2.0";s:8:"language";s:1:"1";s:8:"signType";s:1:"4";s:9:"payerName";s:0:"";s:16:"payerContactType";s:1:"1";s:12:"payerContact";s:14:"2532987@qq.com";s:7:"orderId";s:14:"20171101092653";s:11:"orderAmount";s:1:"1";s:9:"orderTime";s:14:"20171101092653";s:11:"productName";s:6:"苹果";s:10:"productNum";s:1:"5";s:9:"productId";s:8:"55558888";s:11:"productDesc";s:0:"";s:4:"ext1";s:0:"";s:4:"ext2";s:0:"";s:7:"payType";s:2:"00";s:6:"bankId";s:0:"";s:8:"redoFlag";s:0:"";s:3:"pid";s:11:"10209969246";s:7:"signMsg";s:0:"";s:11:"payment_url";s:57:"https://www.99bill.com/gateway/recvMerchantInfoAction.htm";}',
        'config_value'=>'',
        'desc' =>'快钱手机支付',
        'status' => 0,//0未启用 1启用
        'type'=>'payment',
        'icon' => 'logo.jpg',
        'bank_code' =>'',
        'scene' =>1, //0 PC+手机 1手机 2PC
    ],

    //钱包支付
    4=>[
        'code' =>'walletpay',
        'name' =>'钱包支付',
        'version' =>'1.0',
        'author' =>'fzq',
        'config' =>'',
        'config_value'=>'',
        'desc' =>'PC端钱包',
        'status' => 1,//0未启用 1启用
        'type'=>'payment',
        'icon' => 'logo.jpg',
        'bank_code' =>'',
        'scene' =>0, //0 PC+手机 1手机 2PC
    ],

    //钱包支付
    5=>[
        'code' =>'buypay',
        'name' =>'买呗支付',
        'version' =>'1.0',
        'author' =>'fzq',
        'config' =>'',
        'config_value'=>'',
        'desc' =>'PC端买呗',
        'status' => 1,//0未启用 1启用
        'type'=>'payment',
        'icon' => 'logo.jpg',
        'bank_code' =>'',
        'scene' =>0, //0 PC+手机 1手机 2PC
    ],


];

















