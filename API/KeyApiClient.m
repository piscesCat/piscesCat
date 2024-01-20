#import "KeyAPIClient.h"

@implementation KeyAPIClient

@synthesize apiBase;
@synthesize accessToken;

- (void)setAccessToken:(NSString *)accessToken {
    self.accessToken = accessToken;
}

- (KeyAPIClientResponseCode)apiRequest:(NSString *)apiPath params:(NSDictionary *)params {
    NSURL *url = [NSURL URLWithString:[NSString stringWithFormat:@"%@/%@", self.apiBase, apiPath]];
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:url];
    [request setHTTPMethod:@"POST"];
    [request setHTTPBody:[[params JSONString] dataUsingEncoding:NSUTF8StringEncoding]];

    NSError *error;
    NSHTTPURLResponse *response;
    NSData *data = [NSURLConnection sendSynchronousRequest:request returningResponse:&response error:&error];

    if (error) {
        return KeyAPIClientResponseCodeFail;
    }

    NSDictionary *json = [NSJSONSerialization JSONObjectWithData:data options:0 error:&error];

    if (error) {
        return KeyAPIClientResponseCodeFail;
    }

    NSString *status = json[@"status"];

    if ([status isEqualToString:@"success"]) {
        return KeyAPIClientResponseCodeSuccess;
    } else {
        return KeyAPIClientResponseCodeFail;
    }
}

- (KeyAPIClientResponseCode)checkUdid {
    NSDictionary *params = @{@"device_test" : @"test"};

    KeyAPIClientResponseCode code = [self apiRequest:@"/check_udid" params:params];

    if (code == KeyAPIClientResponseCodeSuccess) {
        return code;
    } else {
        return [self requestUdid];
    }
}

- (KeyAPIClientResponseCode)requestUdid {
    NSDictionary *params = @{@"device_test" : @"test"};

    KeyAPIClientResponseCode code = [self apiRequest:@"/request_udid" params:params];

    if (code == KeyAPIClientResponseCodeSuccess) {
        NSString *mobile_config_url = json[@"mobile_config_url"];
        [[UIApplication sharedApplication] openURL:[NSURL URLWithString:mobile_config_url]];
        return code;
    } else {
        return code;
    }
}

- (KeyAPIClientResponseCode)checkKey:(NSString *)key {
    NSDictionary *params = @{@"key" : key};

    KeyAPIClientResponseCode code = [self apiRequest:@"/check_key" params:params];

    if (code == KeyAPIClientResponseCodeSuccess) {
        return code;
    } else {
        return code;
    }
}

- (void)onSuccess:(void (^)(void))completeBlock {
    self.onSuccessBlock = completeBlock;
}

- (void)execute {
    [self checkUdid];
}

@end
