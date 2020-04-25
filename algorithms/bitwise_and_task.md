#SpiralScout LeetCode 1 year challenge
(last month I started SpiralScout LeetCode year challenge)

Bitwise AND of Numbers Range

Interesting LeetCode problem. The description of the problem is the following:

```
Given a range [m, n] where 0 <= m <= n <= 2147483647, return the bitwise AND of all numbers in this range, inclusive.

Example 1:

Input: [5,7]
Output: 4

Example 2:

Input: [0,1]
Output: 0
```

So, there are at least 2 solutions to solve this problem (I'll write it down in my favorite programming language - Rust). First solution is simple, we will go from first number in range over the all presented numbers (last number is included)
```
9   -- |0|0|0|0|1|0|0|1|
10  -- |0|0|0|0|1|0|1|0|
11  -- |0|0|0|0|1|0|1|1|
12  -- |0|0|0|0|1|1|0|0|
```


```rust
    pub fn range_bitwise_and(m: i32, n: i32) -> i32 {
        let mut res = m;
        for i in m..=n {
            res &= i;
        }

        res
    }
```
But imagine if we would have following boundaries: `m = 1` and `n = 2147483647`. Uhh, it would be very heavy operation. If we try it out, it will cost us near ~730ms (it's not a mistake, almost 1 second). Let's turn on our brain and try to optimize this solution.
Let's remember how bitwise AND works: ([Tap](https://en.wikipedia.org/wiki/Bitwise_operation))

```
    0101 (decimal 5)
AND 0011 (decimal 3)
  = 0001 (decimal 1)
```

So, we get 1 bit set only if in both numbers bit is equal to 1, otherwise - bit will set to zero. Let's take a look at the `m = 9` and `n = 12` sequence presented earlier.

```
9   -- |0|0|0|0|1|0|0|1|
10  -- |0|0|0|0|1|0|1|0|
11  -- |0|0|0|0|1|0|1|1|
12  -- |0|0|0|0|1|1|0|0|
```

Do you see that? 1-th column. Oh, I have an idea, 4-th bit is set to 1 in all numbers in that sequence. Good. So, if we found that common bit we could set it in the result number, because as we know, all other bits will be 0 during computation.
Also, we know the size of the number from the description - [2147483647](https://en.wikipedia.org/wiki/2,147,483,647). 32 bits. 
With that knowledge we can iterate from 0 to 32 (exclusive) and try to make a RIGHT SHIFT operation until `m` is less that `n` (remember, by description, m is always less than n). Why? Because when `n` became less or equal than `m` it would mean that we find that common prefix (further will be only zeros), both numbers now have bit 1 in position 0. Why only 2 numbers in that sequence, first and last? Because if we find that prefix on fist and the last numbers it would mean that all bits before will be 0 during AND operation on whole sequence. 
Let's take a look at that sample:
```
9   -- 0 0 0 0 1 0 0 1
12  -- 0 0 0 0 1 1 0 0
```
perform a right shift:
```
4 -- 0 0 0 0 0 1 0 0
6 -- 0 0 0 0 0 1 1 0
```
m < n - moving forward
```
2 -- 0 0 0 0 0 0 1 0
3 -- 0 0 0 0 0 0 1 1
```
m < n - moving forward
```
1 -- 0 0 0 0 0 0 0 1
1 -- 0 0 0 0 0 0 0 1
```
Great, m is equal to n... What's next? We find a prefix (bit) which will be equal to 1. Now, we need to set this bit in result number. LEFT SHIFT TIME :) We performed 3 right shifts, so, let's perform 3 left shift operation on m or n it doesn't matter.
`0 0 0 0 0 0 0 1` << 3 = `0 0 0 0 1 0 0 0`. Result is 2 * 2 * 2 = 8

Let's see the Rust solution:
```rust
    pub fn range_bitwise_and(m: i32, n: i32) -> i32 {
        let mut shift = 0;
        let mut mr = m;
        let mut nr = n;

        while mr < nr {
            mr >>= 1;
            nr >>= 1;
            shift += 1;
        }

        nr << shift
    }
```

Done)