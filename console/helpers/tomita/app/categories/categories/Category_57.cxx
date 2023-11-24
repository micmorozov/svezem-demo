#encoding "utf-8"
#GRAMMAR_ROOT S

Fact -> "мотоцикл" | "снегоход" | "квадроцикл";
S -> Fact interp(Category.Category_57);
