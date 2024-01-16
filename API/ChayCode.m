#import "KeyApiClient.h"

void function(){
    KeyApiClient *API = [[KeyApiClient alloc] init];
    [API setSecretKey:@"test123"];
    [API onSuccess:^{
        NSLog(@"APIData - UDID: %@", [API getUdid]);
   }];
}