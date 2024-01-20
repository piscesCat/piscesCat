KeyAPIClient *client = [[KeyAPIClient alloc] init];
client.apiBase = @"https://api.example.com";
client.accessToken = @"your-access-token";

[client onSuccess:^{
    // Do something when checkKey() returns success
