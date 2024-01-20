#import "APIClient.h"

@interface APIClient ()

@property (nonatomic, strong) AFHTTPSessionManager *sessionManager;

@end

@implementation KeyApiClient

+ (APIClient *)sharedClient {
  static APIClient *sharedClient = nil;
  static dispatch_once_t onceToken;
  dispatch_once(&onceToken, ^{
    sharedClient = [[APIClient alloc] init];
  });
  return sharedClient;
}

- (instancetype)init {
  self = [super init];
  if (self) {
    _sessionManager = [[AFHTTPSessionManager alloc] initWithBaseURL:[NSURL URLWithString:@"https://example.com"]];
    _sessionManager.responseSerializer = [AFJSONResponseSerializer serializer];
  }
  return self;
}

- (void)setApiAccessToken:(NSString *)apiAccessToken {
  _apiAccessToken = apiAccessToken;
}

- (APIClientStatus)apiRequest:(NSDictionary *)params {
  NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:[NSURL URLWithString:[self.apiBase stringByAppendingPathComponent:@"api/v1"]]];
  request.HTTPMethod = @"POST";
  request.HTTPBody = [NSJSONSerialization dataWithJSONObject:params options:0 error:nil];
  request.allHTTPHeaderFields = @{@"Authorization": [NSString stringWithFormat:@"Bearer %@", self.apiAccessToken]};

  NSURLSessionDataTask *task = [self.sessionManager dataTaskWithRequest:request completionHandler:^(NSURLResponse *response, id responseObject, NSError *error) {
    if (error) {
      return;
    }

    NSDictionary *json = responseObject;
    APIClientStatus status = APIClientStatusFail;
    if ([json[@"status"] isEqualToString:@"success"]) {
      status = APIClientStatusSuccess;
    }

    if (self.onSuccess) {
      self.onSuccess();
    }
  }];

  [task resume];

  return status;
}

- (APIClientStatus)checkUdid {
  NSDictionary *params = @{@"device_test": @"test"};
  return [self apiRequest:params];
}

- (APIClientStatus)requestUdid {
  NSDictionary *params = @{@"device_test": @"test"};
  APIClientStatus status = [self apiRequest:params];
  if (status == APIClientStatusSuccess) {
    self.apiBase = json[@"mobile_config_url"];
    [[UIApplication sharedApplication] openURL:[NSURL URLWithString:self.apiBase]];
  }
  return status;
}

- (APIClientStatus)checkKey:(NSString *)key value:(NSString *)value {
  NSDictionary *params = @{@"key": key, @"value": value};
  return [self apiRequest:params];
}

- (void)onSuccess:(void (^)(void))onsuccess {
  self.onSuccess = onsuccess;
}

- (void)execute {
  APIClientStatus status = [self checkUdid];
  if (status == APIClientStatusSuccess) {
    [self checkKey:@"key" value:@"value"];
  }
}

@end
