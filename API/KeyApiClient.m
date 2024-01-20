#import "KeyAPIClient.h"

@implementation KeyAPIClient

@synthesize apiBase;
@synthesize accessToken;

- (void)setAccessToken:(NSString *)accessToken {
    _accessToken = accessToken;
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

- (void)onSuccess:(void (^)(void))completeBlock {
    _onSuccessBlock = completeBlock;
}

- (void)execute {
    [self checkUdid];
}

#pragma mark - Private Methods

- (KeyAPIClientResponseCode)checkUdid {
    NSDictionary *params = @{@"device_test" : @"test"};

    KeyAPIClientResponseCode code = [self apiRequest:@"/check_udid" params:params];

    if (code == KeyAPIClientResponseCodeSuccess) {
        // Do something when checkUdid() returns success
    } else {
        code = [self requestUdid];
    }

    return code;
}

- (KeyAPIClientResponseCode)requestUdid {
    NSDictionary *params = @{@"device_test" : @"test"};

    KeyAPIClientResponseCode code = [self apiRequest:@"/request_udid" params:params];

    if (code == KeyAPIClientResponseCodeSuccess) {
        NSString *mobile_config_url = json[@"mobile_config_url"];
        [[UIApplication sharedApplication] openURL:[NSURL URLWithString:mobile_config_url]];
    }

    return code;
}

- (KeyAPIClientResponseCode)checkKey:(NSString *)key {
    NSDictionary *params = @{@"key" : key};

    KeyAPIClientResponseCode code = [self apiRequest:@"/check_key" params:params];

    if (code == KeyAPIClientResponseCodeSuccess) {
        // Do something when checkKey() returns success
    }

    return code;
}

@end
