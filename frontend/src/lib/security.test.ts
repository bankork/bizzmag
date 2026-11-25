import { describe, expect, it } from "vitest";
import { contentSecurityPolicy } from "./security";

describe("contentSecurityPolicy", () => {
  it("allows the API origin for requests and nothing third-party", () => {
    const policy = contentSecurityPolicy();

    expect(policy).toContain("connect-src 'self' http://localhost:8000");
    expect(policy).toContain("default-src 'self'");
    expect(policy).not.toMatch(/https?:\/\/(?!localhost:8000)/);
  });
});
